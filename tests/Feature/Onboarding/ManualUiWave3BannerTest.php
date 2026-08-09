<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\OnboardingStepsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManualUiWave3BannerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => "Banner's School",
            'email' => 'banner@test.sch.ug',
            'phone' => '0700000044',
            'slug' => 'banner-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Banner Admin',
            'email' => 'admin@banner.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Banner',
            'lastname' => 'Admin',
        ]);
    }

    public function test_dashboard_shows_single_consolidated_setup_banner(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('data-testid="setup-banner"', false);
        $response->assertSee('Set up manually', false);
        $response->assertSee('Set up with Toshi', false);
        $response->assertSee('/admin/onboarding/wizard', false);

        // Old competing boxes must not render alongside the consolidated banner.
        $response->assertDontSee('id="onboarding-reminder"', false);
        $response->assertDontSee('Continue school setup', false);
        $response->assertDontSee('No active plan.</span>', false);
    }

    public function test_setup_banner_covers_incomplete_setup_and_missing_plan(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Finish school setup', false);
        $this->assertTrue(
            OnboardingStepsService::hasIncompleteSteps($this->school->fresh(), $this->admin->id)
        );
        $response->assertSee('not a blocker', false);
    }

    public function test_no_plan_only_banner_when_setup_complete_enough_for_dashboard_gate(): void
    {
        // Give the school an academic year so setupIncomplete trait may clear,
        // but leave onboarding steps incomplete OR complete path carefully.
        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $this->actingAs($this->admin);
        $response = $this->get('/admin/dashboard');
        $response->assertOk();
        $response->assertSee('data-testid="setup-banner"', false);
    }
}
