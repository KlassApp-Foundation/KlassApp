<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\SchoolDetailsController;
use App\Models\Country;
use App\Models\School;
use App\Models\User;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CountryCodeCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_corrects_scrambled_codes_by_name_and_can_run_twice(): void
    {
        DB::table('countries')->insert([
            ['name' => 'Uganda', 'short_name' => 'AF', 'iso_code' => 'AFG', 'tel_prefix' => '256', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'DRC', 'short_name' => 'MA', 'iso_code' => 'MYS', 'tel_prefix' => '60', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kenya', 'short_name' => 'AL', 'iso_code' => 'ALB', 'tel_prefix' => '355', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migration = require database_path('migrations/2026_10_27_120000_correct_country_codes_by_name.php');
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
            'phone' => '0700111222',
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
            'phone' => '0700111333',
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
}
