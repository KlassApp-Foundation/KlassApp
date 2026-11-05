<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The cities migration must not fail or rewrite rows that are already
 * in the table. Production MySQL no longer has state_id. SQLite still
 * does. An orphan state_id must not block the migration.
 */
class CitiesStateColumnMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_missing_state_id_column_is_added_nullable_without_rewriting_rows(): void
    {
        $countryId = $this->countryId();
        $this->recreateCities(withStateId: false);

        $cityId = DB::table('cities')->insertGetId([
            'country_id' => $countryId,
            'name' => 'Kept City',
            'status' => 1,
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2020-01-01 00:00:00',
        ]);

        $this->migration()->up();

        $column = collect(Schema::getColumns('cities'))->firstWhere('name', 'state_id');
        $this->assertNotNull($column);
        $this->assertTrue($column['nullable']);

        $row = DB::table('cities')->where('id', $cityId)->first();
        $this->assertSame('Kept City', $row->name);
        $this->assertSame($countryId, (int) $row->country_id);
        $this->assertNull($row->state_id);
        $this->assertSame('2020-01-01 00:00:00', substr((string) $row->created_at, 0, 19));
        $this->assertSame('2020-01-01 00:00:00', substr((string) $row->updated_at, 0, 19));
        $this->assertFalse($this->stateForeignKeyExists());
    }

    public function test_existing_state_ids_including_an_orphan_are_left_unchanged_and_the_foreign_key_is_not_added(): void
    {
        $countryId = $this->countryId();
        $stateId = DB::table('states')->insertGetId([
            'country_id' => $countryId,
            'name' => 'Central',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->recreateCities(withStateId: true);

        $validId = DB::table('cities')->insertGetId([
            'country_id' => $countryId,
            'state_id' => $stateId,
            'name' => 'Valid City',
            'status' => 1,
            'created_at' => '2020-02-02 00:00:00',
            'updated_at' => '2020-02-02 00:00:00',
        ]);
        $nullId = DB::table('cities')->insertGetId([
            'country_id' => $countryId,
            'state_id' => null,
            'name' => 'Unassigned City',
            'status' => 1,
            'created_at' => '2020-02-03 00:00:00',
            'updated_at' => '2020-02-03 00:00:00',
        ]);
        $orphanId = DB::table('cities')->insertGetId([
            'country_id' => $countryId,
            'state_id' => 999999,
            'name' => 'Orphan City',
            'status' => 1,
            'created_at' => '2020-03-03 00:00:00',
            'updated_at' => '2020-03-03 00:00:00',
        ]);

        $warnings = [];
        Log::listen(function (MessageLogged $event) use (&$warnings): void {
            if ($event->level === 'warning' && str_contains($event->message, 'foreign key was not added')) {
                $warnings[] = $event;
            }
        });

        $this->migration()->up();

        $valid = DB::table('cities')->where('id', $validId)->first();
        $unassigned = DB::table('cities')->where('id', $nullId)->first();
        $orphan = DB::table('cities')->where('id', $orphanId)->first();

        $this->assertSame($stateId, (int) $valid->state_id);
        $this->assertSame('Valid City', $valid->name);
        $this->assertNull($unassigned->state_id);
        $this->assertSame('Unassigned City', $unassigned->name);
        $this->assertSame(999999, (int) $orphan->state_id);
        $this->assertSame('Orphan City', $orphan->name);
        $this->assertSame('2020-03-03 00:00:00', substr((string) $orphan->updated_at, 0, 19));
        $this->assertNotEmpty($warnings);
        $this->assertSame(1, $warnings[0]->context['orphans'] ?? null);
        $this->assertFalse($this->stateForeignKeyExists());
    }

    private function countryId(): int
    {
        return (int) DB::table('countries')->insertGetId([
            'name' => 'Migration Land',
            'short_name' => 'ML',
            'iso_code' => 'MLD',
            'tel_prefix' => '0',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Replace cities with a table that has no state foreign key, so the
     * migration is the thing that decides whether to add one. The column
     * is omitted when $withStateId is false.
     */
    private function recreateCities(bool $withStateId): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('DROP TABLE cities');
        $stateColumn = $withStateId ? 'state_id INTEGER NULL,' : '';
        DB::statement("
            CREATE TABLE cities (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                country_id INTEGER NOT NULL,
                {$stateColumn}
                name TEXT NOT NULL,
                status TINYINT NOT NULL DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL
            )
        ");
        Schema::enableForeignKeyConstraints();
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_11_03_140000_ensure_city_state_and_reference_catalogs.php');
    }

    private function stateForeignKeyExists(): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            $row = DB::selectOne(
                'select CONSTRAINT_NAME from information_schema.TABLE_CONSTRAINTS where TABLE_SCHEMA = DATABASE() and TABLE_NAME = ? and CONSTRAINT_NAME = ?',
                ['cities', 'cities_state_id_foreign']
            );

            return $row !== null;
        }

        foreach (DB::select('PRAGMA foreign_key_list(cities)') as $key) {
            if (($key->from ?? null) === 'state_id' && ($key->table ?? null) === 'states') {
                return true;
            }
        }

        return false;
    }
}
