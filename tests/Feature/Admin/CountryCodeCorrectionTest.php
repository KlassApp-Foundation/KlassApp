<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\SchoolDetailsController;
use App\Models\Country;
use App\Models\School;
use App\Models\User;
use App\Services\OnboardingStepsService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;
use Tests\TestCase;

class CountryCodeCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    private array $correctionLogs = [];

    public function test_migration_corrects_scrambled_codes_by_name_and_can_run_twice(): void
    {
        DB::table('countries')->insert([
            ['name' => 'Uganda', 'short_name' => 'AF', 'iso_code' => 'AFG', 'tel_prefix' => '256', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'DRC', 'short_name' => 'MA', 'iso_code' => 'MYS', 'tel_prefix' => '60', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kenya', 'short_name' => 'AL', 'iso_code' => 'ALB', 'tel_prefix' => '355', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migration = $this->migration();
        $migration->up();
        $migration->up();

        $uganda = Country::where('name', 'Uganda')->first();
        $drc = Country::where('name', 'DRC')->first();
        $kenya = Country::where('name', 'Kenya')->first();

        $this->assertSame('UG', $uganda->short_name);
        $this->assertSame('UGA', $uganda->iso_code);
        $this->assertSame('256', (string) $uganda->tel_prefix);
        $this->assertSame('CD', $drc->short_name);
        $this->assertSame('COD', $drc->iso_code);
        $this->assertSame('243', (string) $drc->tel_prefix);
        $this->assertSame('KE', $kenya->short_name);
        $this->assertNull(Country::where('short_name', 'MA')->first());
        $this->assertNull(Country::where('short_name', 'AF')->first());
    }

    public function test_a_failed_correction_rolls_back_the_temporary_short_names(): void
    {
        $ugandaId = DB::table('countries')->insertGetId([
            'name' => 'Uganda', 'short_name' => 'AF', 'iso_code' => 'AFG', 'tel_prefix' => '93', 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $kenyaId = DB::table('countries')->insertGetId([
            'name' => 'Kenya', 'short_name' => 'AL', 'iso_code' => 'ALB', 'tel_prefix' => '355', 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('countries')->insert([
            'name' => 'Park Collision',
            'short_name' => 'T'.$kenyaId,
            'iso_code' => 'ZZZ',
            'tel_prefix' => '999',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->captureCorrectionLogs();

        try {
            $this->migration()->up();
            $this->fail('The temporary short name collision should abort the correction.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('short_name', $e->getMessage());
        }

        $uganda = DB::table('countries')->where('id', $ugandaId)->first();
        $kenya = DB::table('countries')->where('id', $kenyaId)->first();
        $this->assertSame('AF', $uganda->short_name);
        $this->assertSame('AFG', $uganda->iso_code);
        $this->assertSame('AL', $kenya->short_name);
        $this->assertSame([], array_values(array_filter(
            $this->correctionLogs,
            fn (array $event): bool => $event['message'] === 'Country code correction finished.'
        )));
    }

    public function test_a_skipped_row_is_logged_with_its_id_and_reason_and_totals_match(): void
    {
        $blockerId = DB::table('countries')->insertGetId([
            'name' => 'Placeholder Land',
            'short_name' => 'UG',
            'iso_code' => 'ZZZ',
            'tel_prefix' => '999',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $ugandaId = DB::table('countries')->insertGetId([
            'name' => 'Uganda',
            'short_name' => 'AF',
            'iso_code' => 'AFG',
            'tel_prefix' => '93',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('countries')->insert([
            'name' => 'Kenya', 'short_name' => 'AL', 'iso_code' => 'ALB', 'tel_prefix' => '355', 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->captureCorrectionLogs();
        $this->migration()->up();

        $uganda = DB::table('countries')->where('id', $ugandaId)->first();
        $this->assertSame('AF', $uganda->short_name);
        $this->assertSame('AFG', $uganda->iso_code);
        $this->assertSame('KE', Country::where('name', 'Kenya')->first()->short_name);
        $this->assertSame('UG', DB::table('countries')->where('id', $blockerId)->value('short_name'));

        $skips = array_values(array_filter(
            $this->correctionLogs,
            fn (array $event): bool => $event['level'] === 'warning'
                && $event['message'] === 'Country code correction skipped a row.'
        ));
        $summaries = array_values(array_filter(
            $this->correctionLogs,
            fn (array $event): bool => $event['level'] === 'info'
                && $event['message'] === 'Country code correction finished.'
        ));

        $ugandaSkip = null;
        foreach ($skips as $skip) {
            $blob = json_encode($skip['context']);
            $this->assertIsString($blob);
            $this->assertStringNotContainsString('@', $blob);
            $this->assertArrayNotHasKey('email', $skip['context']);
            $this->assertArrayNotHasKey('phone', $skip['context']);
            if ((int) ($skip['context']['countries_id'] ?? 0) === (int) $ugandaId) {
                $ugandaSkip = $skip;
            }
        }

        $this->assertNotNull($ugandaSkip);
        $this->assertSame($ugandaId, $ugandaSkip['context']['countries_id']);
        $this->assertSame(
            'target short_name is already used by another countries row',
            $ugandaSkip['context']['reason']
        );
        $this->assertSame($blockerId, $ugandaSkip['context']['blocking_countries_id']);

        $this->assertCount(1, $summaries);
        $this->assertSame(count($skips), $summaries[0]['context']['skipped']);
        $this->assertSame(1, $summaries[0]['context']['corrected']);
        $this->assertSame(9, $summaries[0]['context']['skipped']);
    }

    public function test_down_is_empty_and_leaves_corrected_codes_in_place(): void
    {
        DB::table('countries')->insert([
            'name' => 'Uganda', 'short_name' => 'AF', 'iso_code' => 'AFG', 'tel_prefix' => '93', 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $migration = $this->migration();
        $migration->up();

        $method = new ReflectionMethod($migration, 'down');
        $source = implode('', array_slice(
            file($method->getFileName()),
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));
        $this->assertDoesNotMatchRegularExpression('/\b(DB|Schema)::/', $source);

        $before = DB::table('countries')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $migration->down();
        $after = DB::table('countries')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();

        $this->assertSame($before, $after);
        $this->assertSame('UG', DB::table('countries')->where('name', 'Uganda')->value('short_name'));
        $this->assertSame('UGA', DB::table('countries')->where('name', 'Uganda')->value('iso_code'));
    }

    public function test_saving_either_country_field_writes_the_other(): void
    {
        $uganda = Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'iso_code' => 'UGA',
            'tel_prefix' => '256',
            'status' => 1,
        ]);
        $kenya = Country::create([
            'name' => 'Kenya',
            'short_name' => 'KE',
            'iso_code' => 'KEN',
            'tel_prefix' => '254',
            'status' => 1,
        ]);

        DB::table('usergroups')->insert([
            'id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'name' => 'Sync School',
            'email' => 'sync@school.test',
            'phone' => '0000000001',
            'slug' => 'sync-school',
            'status' => 1,
            'toshi_enabled' => 0,
        ]);
        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Sync Admin',
            'email' => 'sync-admin@school.test',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $this->actingAs($admin);
        app(SchoolDetailsController::class)->store(Request::create('/admin/schooldetails/store', 'POST', [
            'name' => 'Sync School',
            'address' => 'Plot 1',
            'country_id' => $kenya->id,
        ]));

        $school->refresh();
        $this->assertSame($kenya->id, (int) $school->country_id);
        $this->assertSame('Kenya', $school->registration_country);

        $named = School::create([
            'name' => 'Named Only',
            'email' => 'named@school.test',
            'phone' => '0000000002',
            'slug' => 'named-only',
            'status' => 1,
            'toshi_enabled' => 0,
            'registration_country' => 'Uganda',
        ]);
        OnboardingStepsService::persistCountry($named, 'UG');
        $named->refresh();
        $this->assertSame($uganda->id, (int) $named->country_id);
        $this->assertSame('UG', $named->registration_country);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_27_120000_correct_country_codes_by_name.php');
    }

    private function captureCorrectionLogs(): void
    {
        $this->correctionLogs = [];
        Log::listen(function (MessageLogged $event): void {
            if (! str_starts_with($event->message, 'Country code correction')) {
                return;
            }
            $this->correctionLogs[] = [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ];
        });
    }
}
