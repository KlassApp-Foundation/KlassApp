<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite keeps cities.state_id because the earlier drop skipped SQLite.
 * MySQL dropped the column. Put a nullable column back only when it is
 * missing. Existing values are not updated, cleared, or dropped.
 *
 * The foreign key is added only when every non-null state_id already
 * points at a state. Orphans are left as they are and reported by
 * db:health. A key that is already there is not recreated.
 *
 * Also stores the curriculum list and the grading bands from
 * config/grading_uganda.php. Those catalogs are not school rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cities') && ! Schema::hasColumn('cities', 'state_id')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->unsignedInteger('state_id')->nullable();
            });
        }

        $this->addStateForeignKeyIfSafe();

        if (! Schema::hasTable('reference_curricula')) {
            Schema::create('reference_curricula', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 32)->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reference_grading_bands')) {
            Schema::create('reference_grading_bands', function (Blueprint $table) {
                $table->increments('id');
                $table->string('level', 32);
                $table->unsignedSmallInteger('position');
                $table->string('grade', 64);
                $table->integer('points')->nullable();
                $table->unsignedTinyInteger('min_score')->nullable();
                $table->unsignedTinyInteger('max_score')->nullable();
                $table->string('remark')->nullable();
                $table->timestamps();
                $table->unique(['level', 'position']);
                $table->unique(['level', 'grade']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_grading_bands');
        Schema::dropIfExists('reference_curricula');

        // The original cities table owns state_id on SQLite. Only drop
        // the foreign key this migration added on MySQL.
        if (Schema::hasTable('cities') && $this->foreignKeyExists('cities', 'cities_state_id_foreign')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->dropForeign('cities_state_id_foreign');
            });
        }
    }

    /**
     * Does not update cities. A null state_id is allowed by the key.
     * A non-null state_id that does not match states.id blocks the key
     * and is left in place.
     */
    private function addStateForeignKeyIfSafe(): void
    {
        if (! Schema::hasTable('cities') || ! Schema::hasColumn('cities', 'state_id')) {
            return;
        }

        if ($this->stateForeignKeyExists()) {
            return;
        }

        $orphans = $this->orphanStateCount();
        if ($orphans > 0) {
            Log::warning('cities.state_id foreign key was not added. Existing values were left unchanged.', [
                'orphans' => $orphans,
            ]);

            return;
        }

        // SQLite cannot add a foreign key without rebuilding the table.
        // Rebuilding would copy every row. The create-table migration
        // already declares the key when the column was created with the
        // table. A column added here stays without a new key.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('cities', function (Blueprint $table) {
            $table->foreign('state_id', 'cities_state_id_foreign')
                ->references('id')
                ->on('states');
        });
    }

    private function orphanStateCount(): int
    {
        return (int) DB::table('cities')
            ->whereNotNull('cities.state_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('states')
                    ->whereColumn('states.id', 'cities.state_id');
            })
            ->count();
    }

    private function stateForeignKeyExists(): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach (DB::select('PRAGMA foreign_key_list(cities)') as $key) {
                if (($key->from ?? null) === 'state_id' && ($key->table ?? null) === 'states') {
                    return true;
                }
            }

            return false;
        }

        return $this->foreignKeyExists('cities', 'cities_state_id_foreign');
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $row = DB::selectOne(
            'select CONSTRAINT_NAME from information_schema.TABLE_CONSTRAINTS where TABLE_SCHEMA = DATABASE() and TABLE_NAME = ? and CONSTRAINT_NAME = ? and CONSTRAINT_TYPE = ?',
            [$table, $name, 'FOREIGN KEY']
        );

        return $row !== null;
    }
};
