<?php

namespace Tests\Feature\Dashboard;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Support\DashboardGreeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class DashboardQuickFixesTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Dash Fix School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'mucunguzi.loginhandle',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt(Str::random(16)),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'firstname' => 'MUCUNGUZI',
            'lastname' => 'RASTA',
            'status' => 'active',
        ]);
    }

    public function test_greeting_uses_title_cased_profile_firstname_not_login_handle(): void
    {
        $greeting = DashboardGreeting::for($this->admin->fresh(), 'Admin');

        $this->assertSame('Mucunguzi', $greeting['name']);
        $this->assertStringNotContainsString('mucunguzi.loginhandle', $greeting['name']);
        $this->assertNotSame('MUCUNGUZI', $greeting['name']);
    }

    public function test_greeting_falls_back_when_firstname_empty_without_using_users_name(): void
    {
        $this->admin->userprofile->update(['firstname' => '']);

        $greeting = DashboardGreeting::for($this->admin->fresh(), 'Admin');

        $this->assertSame('Admin', $greeting['name']);
    }

    public function test_setup_banner_has_no_space_before_period_and_uses_service_labels(): void
    {
        // Red without the banner fix: "7 steps remaining ." and ucfirst keys for missing STEP_LABELS entries.
        $this->actingAs($this->admin);

        $html = View::make('partials.setup-banner', [
            'setupIncomplete' => true,
            'onboardingMissing' => ['school_category', 'students', 'fees'],
            'onboardingSteps' => [
                ['key' => 'school_category', 'label' => 'School category'],
                ['key' => 'students', 'label' => 'Students'],
                ['key' => 'fees', 'label' => 'Fee structures'],
                ['key' => 'a', 'label' => 'A'],
                ['key' => 'b', 'label' => 'B'],
                ['key' => 'c', 'label' => 'C'],
                ['key' => 'd', 'label' => 'D'],
            ],
        ])->render();

        $this->assertStringContainsString('7 steps remaining.', $html);
        $this->assertStringNotContainsString('remaining .', $html);
        $this->assertStringNotContainsString('School_category', $html);

        $htmlFew = View::make('partials.setup-banner', [
            'setupIncomplete' => true,
            'onboardingMissing' => ['school_category'],
            'onboardingSteps' => [
                ['key' => 'school_category', 'label' => 'School category'],
            ],
        ])->render();

        $this->assertStringContainsString('1 step remaining (School category).', $htmlFew);
    }

    public function test_academic_year_selector_is_visible_in_header_markup_on_all_widths(): void
    {
        $nav = file_get_contents(resource_path('views/layouts/partials/navigation.blade.php'));
        $vue = file_get_contents(resource_path('assets/js/components/Navigation.vue'));

        $this->assertStringContainsString('dashboard-ay-selector', $nav);
        $this->assertStringNotContainsString('hidden lg:block md:block', $nav);
        $this->assertStringContainsString('Academic year', $vue);
        $this->assertStringNotContainsString('hidden lg:block', $vue);
    }

    public function test_onboarding_reminder_partial_is_removed(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/partials/onboarding-reminder.blade.php'));
    }
}
