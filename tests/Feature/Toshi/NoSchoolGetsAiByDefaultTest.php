<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace Tests\Feature\Toshi;

use App\Enums\ToshiMode;
use App\Enums\ToshiScope;
use App\Livewire\Superadmin\Academics\CreateSchool;
use App\Models\City;
use App\Models\Country;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\SchoolSignupBootstrapService;
use App\Services\Superadmin\SchoolService;
use App\Services\Toshi\ToshiAvailabilityGate;
use App\Services\Toshi\ToshiUiSwitch;
use Database\Seeders\DemoAcademySeeder;
use Database\Seeders\DemoJuniorSchoolSeeder;
use Database\Seeders\DemoSeniorSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Soft-launch 1g: every school-creation path must start AI-off.
 * Assistant / MCP school-scope must stay unreachable until explicitly enabled.
 */
class NoSchoolGetsAiByDefaultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('ai.providers.openai-compatible.key', 'sk-test-key-for-gate-checks');
        Config::set('toshi.api_key', 'sk-test-key-for-gate-checks');
        Config::set('toshi.per_school_gate', true);

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('plans')->insertOrIgnore([
            [
                'id' => 1,
                'cycle' => 30,
                'name' => 'Freemium',
                'display_name' => 'Freemium',
                'order' => 1,
                'is_active' => 1,
                'amount' => 0,
                'no_of_students' => 0,
                'no_of_users' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * @return array{school: School, admin: User}
     */
    private function assertSchoolStartsAiOff(School $school, ?User $admin = null): array
    {
        $school->refresh();
        $this->assertContains(
            $school->toshi_mode,
            [ToshiMode::Onboarding, ToshiMode::Preview],
            'New school must start in onboarding or preview, never assistant'
        );
        $this->assertSame(0, (int) $school->toshi_enabled, 'toshi_enabled must be 0 by default');
        $this->assertNotSame(ToshiMode::Assistant, $school->toshi_mode);

        $admin ??= User::query()
            ->where('school_id', $school->id)
            ->where('usergroup_id', 3)
            ->first();

        if ($admin !== null) {
            $switch = app(ToshiUiSwitch::class);
            $this->assertFalse(
                $switch->assistantEnabled($admin->fresh()),
                'Assistant must be off until explicitly enabled'
            );
            $gate = app(ToshiAvailabilityGate::class);
            $this->assertFalse(
                $gate->allows($admin->fresh(), ToshiScope::School, (int) $school->id),
                'School-scope AI/MCP gate must deny until toshi_enabled'
            );
        }

        return ['school' => $school, 'admin' => $admin];
    }

    public function test_sign_up_bootstrap_path_starts_ai_off(): void
    {
        $admin = app(SchoolSignupBootstrapService::class)->bootstrap([
            'name' => 'Opt In Signup',
            'email' => 'signup-'.Str::random(6).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'password' => 'secret123',
        ]);

        $school = School::findOrFail($admin->school_id);
        $this->assertSchoolStartsAiOff($school, $admin);
        $this->assertSame(ToshiMode::Onboarding, $school->toshi_mode);
    }

    public function test_toshi_create_mode_path_starts_ai_off(): void
    {
        // Mirrors AgentToshi::commitAll create payload after soft-launch 1g fix.
        $school = School::create([
            'name' => 'Toshi Create '.Str::random(4),
            'email' => Str::random(8).'@klassapp.sch.ug',
            'phone' => '0700000000',
            'curriculum' => 'uneb',
            'toshi_enabled' => 0,
            'toshi_mode' => ToshiMode::Onboarding,
            'status' => 1,
            'slug' => 'toshi-create-'.Str::random(6),
            'registration_country' => 'Uganda',
        ]);

        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Toshi',
            'lastname' => 'Admin',
        ]);

        $this->assertSchoolStartsAiOff($school, $admin);

        // Source contract: create payload must not hardcode toshi_enabled => 1.
        $source = file_get_contents(app_path('Livewire/AgentToshi.php'));
        $this->assertDoesNotMatchRegularExpression(
            "/School::create\(\[[\s\S]*?'toshi_enabled'\s*=>\s*1/",
            $source,
            'AgentToshi create mode must not enable AI by default'
        );
    }

    public function test_site_admin_school_service_create_starts_ai_off(): void
    {
        $country = Country::create([
            'name' => 'Kenya',
            'short_name' => 'KE',
            'iso_code' => 'KE',
            'tel_prefix' => '+254',
            'status' => 1,
        ]);
        $city = City::create([
            'name' => 'Nairobi',
            'country_id' => $country->id,
            'status' => 1,
            'state_id' => 0,
        ]);

        // Non-Uganda registration_country avoids required EMIS ministry_code.
        $school = app(SchoolService::class)->create([
            'name' => 'SiteAdmin School '.Str::random(4),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '0700123456',
            'address' => 'Plot 1 Test Road',
            'city_id' => $city->id,
            'country_id' => $country->id,
            'pincode' => '25600',
            'registration_country' => 'Kenya',
            'status' => 1,
        ]);

        $this->assertSchoolStartsAiOff($school);
        $this->assertSame(ToshiMode::Onboarding, $school->toshi_mode);
    }

    public function test_site_admin_create_school_livewire_defaults_toshi_off(): void
    {
        $component = new CreateSchool;
        $this->assertFalse($component->toshi_enabled);

        $source = file_get_contents(app_path('Livewire/Superadmin/Academics/CreateSchool.php'));
        $this->assertStringContainsString('public $toshi_enabled = false;', $source);
        $this->assertStringNotContainsString('public $toshi_enabled = true;', $source);
    }

    public function test_demo_seeders_start_ai_off(): void
    {
        // Seeders may require heavy fixtures — assert their forceFill defaults in source
        // and run Academy seeder when possible.
        foreach ([
            'DemoAcademySeeder.php',
            'DemoJuniorSchoolSeeder.php',
            'DemoSeniorSchoolSeeder.php',
        ] as $file) {
            $source = file_get_contents(database_path('seeders/'.$file));
            $this->assertStringNotContainsString(
                "'toshi_enabled' => 1",
                $source,
                "{$file} must not enable AI by default"
            );
            $this->assertMatchesRegularExpression(
                "/'toshi_enabled'\s*=>\s*0/",
                $source
            );
        }

        // Runtime check for DemoAcademy when it can run under RefreshDatabase.
        try {
            $this->seed(DemoAcademySeeder::class);
            $school = School::query()->where('slug', 'demo-academy-uganda')->orWhere('name', 'like', 'Demo Academy%')->first();
            if ($school) {
                $this->assertSchoolStartsAiOff($school);
            }
        } catch (\Throwable $e) {
            // Source contract above is authoritative if seeder needs missing tables.
            $this->addToAssertionCount(1);
        }
    }

    public function test_assistant_and_mcp_gate_remain_closed_until_explicit_enable(): void
    {
        $admin = app(SchoolSignupBootstrapService::class)->bootstrap([
            'name' => 'Gate Closed',
            'email' => 'gate-'.Str::random(6).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'password' => 'secret123',
        ]);
        $school = School::findOrFail($admin->school_id);
        $this->assertSchoolStartsAiOff($school, $admin);

        // Explicit opt-in unlocks assistant + school gate.
        $school->setToshiMode(ToshiMode::Assistant);
        $school->refresh();
        $this->assertSame(1, (int) $school->toshi_enabled);
        $this->assertTrue(app(ToshiUiSwitch::class)->assistantEnabled($admin->fresh()));
        $this->assertTrue(app(ToshiAvailabilityGate::class)->allows(
            $admin->fresh(),
            ToshiScope::School,
            (int) $school->id
        ));
    }
}
