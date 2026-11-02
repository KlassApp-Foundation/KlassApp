<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\SchoolDetailsController;
use App\Models\City;
use App\Models\Country;
use App\Models\School;
use App\Models\User;
use App\Services\OnboardingStepsService;
use App\Services\SchoolSignupBootstrapService;
use App\Services\Superadmin\SchoolService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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

    public function test_down_restores_the_seed_codes_and_up_can_run_again(): void
    {
        DB::table('countries')->insert([
            ['name' => 'Uganda', 'short_name' => 'AF', 'iso_code' => 'AFG', 'tel_prefix' => '256', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kenya', 'short_name' => 'AL', 'iso_code' => 'ALB', 'tel_prefix' => '355', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'DRC', 'short_name' => 'MA', 'iso_code' => 'MYS', 'tel_prefix' => '60', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Other', 'short_name' => 'OT', 'iso_code' => 'OT', 'tel_prefix' => '20', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migration = $this->migration();
        $migration->up();
        $this->assertSame('UG', DB::table('countries')->where('name', 'Uganda')->value('short_name'));
        $this->assertSame('OTH', DB::table('countries')->where('name', 'Other')->value('iso_code'));

        $migration->down();

        $uganda = DB::table('countries')->where('name', 'Uganda')->first();
        $kenya = DB::table('countries')->where('name', 'Kenya')->first();
        $drc = DB::table('countries')->where('name', 'DRC')->first();
        $other = DB::table('countries')->where('name', 'Other')->first();
        $this->assertSame('AF', $uganda->short_name);
        $this->assertSame('AFG', $uganda->iso_code);
        $this->assertSame('256', (string) $uganda->tel_prefix);
        $this->assertSame('AL', $kenya->short_name);
        $this->assertSame('ALB', $kenya->iso_code);
        $this->assertSame('MA', $drc->short_name);
        $this->assertSame('MYS', $drc->iso_code);
        $this->assertSame('60', (string) $drc->tel_prefix);
        $this->assertSame('OT', $other->short_name);
        $this->assertSame('OT', $other->iso_code);
        $this->assertSame('20', (string) $other->tel_prefix);

        $migration->up();
        $this->assertSame('UG', DB::table('countries')->where('name', 'Uganda')->value('short_name'));
        $this->assertSame('UGA', DB::table('countries')->where('name', 'Uganda')->value('iso_code'));
        $this->assertSame('KE', DB::table('countries')->where('name', 'Kenya')->value('short_name'));
        $this->assertSame('CD', DB::table('countries')->where('name', 'DRC')->value('short_name'));
    }

    public function test_a_soft_deleted_country_is_not_corrected(): void
    {
        $trashedId = DB::table('countries')->insertGetId([
            'name' => 'Uganda',
            'short_name' => 'AF',
            'iso_code' => 'AFG',
            'tel_prefix' => '256',
            'status' => 1,
            'deleted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('countries')->insert([
            'name' => 'Kenya', 'short_name' => 'AL', 'iso_code' => 'ALB', 'tel_prefix' => '355', 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->migration()->up();

        $trashed = DB::table('countries')->where('id', $trashedId)->first();
        $this->assertSame('AF', $trashed->short_name);
        $this->assertSame('AFG', $trashed->iso_code);
        $this->assertSame('KE', DB::table('countries')->where('name', 'Kenya')->value('short_name'));
    }

    public function test_two_live_rows_with_the_same_name_are_left_unchanged_and_logged(): void
    {
        DB::table('countries')->insert([
            ['name' => 'Uganda', 'short_name' => 'AF', 'iso_code' => 'AFG', 'tel_prefix' => '256', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Uganda', 'short_name' => 'ZZ', 'iso_code' => 'ZZZ', 'tel_prefix' => '999', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kenya', 'short_name' => 'AL', 'iso_code' => 'ALB', 'tel_prefix' => '355', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->captureCorrectionLogs();
        $this->migration()->up();

        $ugandas = DB::table('countries')->where('name', 'Uganda')->orderBy('id')->get();
        $this->assertSame('AF', $ugandas[0]->short_name);
        $this->assertSame('ZZ', $ugandas[1]->short_name);
        $this->assertSame('KE', DB::table('countries')->where('name', 'Kenya')->value('short_name'));

        $duplicate = null;
        foreach ($this->correctionLogs as $event) {
            if ($event['message'] === 'Country code correction skipped a row.'
                && ($event['context']['reason'] ?? '') === 'more than one countries row matched this name'
                && ($event['context']['name'] ?? '') === 'Uganda') {
                $duplicate = $event;
            }
        }
        $this->assertNotNull($duplicate);
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

        $city = City::create([
            'name' => 'Kampala',
            'country_id' => $kenya->id,
            'status' => 1,
            'state_id' => 0,
        ]);

        $this->actingAs($admin);
        app(SchoolDetailsController::class)->store(Request::create('/admin/schooldetails/store', 'POST', [
            'name' => 'Sync School Renamed',
            'address' => 'Plot 1',
            'country_id' => $kenya->id,
            'city_id' => $city->id,
        ]));

        $school->refresh();
        $this->assertSame($kenya->id, (int) $school->country_id);
        $this->assertSame('Kenya', $school->registration_country);
        $this->assertSame('Sync School Renamed', $school->name);
        $this->assertSame('Plot 1', $school->address);
        $this->assertSame($city->id, (int) $school->city_id);

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
        $this->assertSame('Uganda', $named->registration_country);
    }

    public function test_a_country_id_and_a_different_country_name_are_rejected(): void
    {
        [$uganda, $kenya, $city] = $this->ugandaKenyaAndCity();
        $school = School::create([
            'name' => 'Disagree School',
            'email' => 'disagree@school.test',
            'phone' => '0000000003',
            'slug' => 'disagree-school',
            'status' => 1,
            'toshi_enabled' => 0,
            'country_id' => $uganda->id,
            'registration_country' => 'Uganda',
            'city_id' => $city->id,
            'address' => 'Plot 1',
            'pincode' => '25600',
        ]);

        try {
            app(SchoolService::class)->update($school->id, $this->schoolPayload($school, $city, [
                'country_id' => $uganda->id,
                'registration_country' => 'Kenya',
            ]));
            $this->fail('A country id and a different country name must not be saved.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'The country and the country name do not match.',
                $e->errors()['registration_country'][0] ?? null,
            );
        }

        $school->refresh();
        $this->assertSame($uganda->id, (int) $school->country_id);
        $this->assertSame('Uganda', $school->registration_country);
        $this->assertNotSame($kenya->id, (int) $school->country_id);
    }

    public function test_a_short_code_is_saved_as_the_country_name(): void
    {
        [$uganda, $kenya, $city] = $this->ugandaKenyaAndCity();
        $school = School::create([
            'name' => 'Code School',
            'email' => 'code@school.test',
            'phone' => '0000000004',
            'slug' => 'code-school',
            'status' => 1,
            'toshi_enabled' => 0,
            'country_id' => $uganda->id,
            'registration_country' => 'Uganda',
            'city_id' => $city->id,
            'address' => 'Plot 1',
            'pincode' => '25600',
        ]);

        $saved = app(SchoolService::class)->update($school->id, $this->schoolPayload($school, $city, [
            'country_id' => $kenya->id,
            'registration_country' => 'KE',
        ]));

        $this->assertSame($kenya->id, (int) $saved->country_id);
        $this->assertSame('Kenya', $saved->registration_country);
    }

    public function test_signup_stores_the_country_name_when_given_a_short_code(): void
    {
        [, $kenya] = $this->ugandaKenyaAndCity();
        DB::table('usergroups')->insert([
            'id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $user = app(SchoolSignupBootstrapService::class)->bootstrapAsOperator([
            'name' => 'Signup Admin',
            'email' => 'signup-code@school.test',
            'phone' => '0700000099',
            'password' => 'secret123',
            'registration_country' => 'KE',
        ]);

        $this->assertSame('Kenya', $user->school->registration_country);
        $this->assertSame($kenya->id, (int) $user->school->country_id);
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

    /**
     * @return array{0: Country, 1: Country, 2: City}
     */
    private function ugandaKenyaAndCity(): array
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
        $city = City::create([
            'name' => 'Test City',
            'country_id' => $uganda->id,
            'status' => 1,
            'state_id' => 0,
        ]);

        return [$uganda, $kenya, $city];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function schoolPayload(School $school, City $city, array $overrides): array
    {
        return array_merge([
            'name' => $school->name,
            'email' => $school->email,
            'phone' => '0712345678',
            'address' => 'Plot 1',
            'city_id' => $city->id,
            'country_id' => $school->country_id,
            'pincode' => '25600',
            'status' => 1,
        ], $overrides);
    }
}
