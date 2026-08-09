<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Country;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardEmptyStatePlanToProfileTest extends TestCase
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

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => "Empty's School",
            'email' => 'empty@test.sch.ug',
            'phone' => '0700000066',
            'slug' => 'empty-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Empty Admin',
            'email' => 'admin@empty.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Empty',
            'lastname' => 'Admin',
        ]);
    }

    public function test_pre_onboarding_dashboard_hides_plan_card_and_kpi_cards(): void
    {
        $plan = Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);
        CurrentPlan::create([
            'school_id' => $this->school->id,
            'plan_id' => $plan->id,
            'status' => 'running',
        ]);

        $this->actingAs($this->admin);
        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('data-testid="setup-banner"', false);
        $response->assertSee('data-testid="dashboard-kpi-placeholder"', false);

        $response->assertDontSee('data-testid="plan-usage-banner"', false);
        $response->assertDontSee('Freemium Plan', false);
        $response->assertDontSee('dashboard-kpi-card', false);
        $response->assertDontSee('dashboard-kpi-label', false);
        $response->assertDontSee('dashboard-notice-card', false);
        $response->assertDontSee('id="graph"', false);
    }

    public function test_post_onboarding_dashboard_shows_kpis_without_plan_card(): void
    {
        $plan = Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);

        $view = $this->view('admin.dashboard.dashboard', [
            'dashboard' => [
                'studentCount' => 12,
                'teacherCount' => 3,
                'parentCount' => 8,
                'nonteachingCount' => 1,
                'femaleCount' => 6,
                'maleCount' => 6,
                'setupIncomplete' => false,
                'whatsapp' => ['parentsOptedIn' => 2, 'messagesThisMonth' => 5],
                'noticeboard' => [],
                'feedbacks' => [],
                'events' => [],
                'products' => [],
                'upcomingExam' => [],
                'standardStudentCounts' => collect(),
                'work' => [],
                'task' => [],
                'event' => [],
                'reminder' => [],
                'fee' => [],
                'birthday' => [],
            ],
            'standardLink' => null,
            'selected_teacher' => null,
            'plan' => $plan,
            'planUsage' => [
                'students' => ['used' => 12, 'limit' => 0],
                'teachers' => ['used' => 3, 'limit' => 0],
            ],
            'onboardingMissing' => [],
            'onboardingSteps' => [],
            'setupIncomplete' => false,
            'openToshiOnboarding' => false,
            'pendingApprovals' => 0,
            'trendPeriod' => 'month',
            'feeTrend' => ['labels' => [], 'values' => []],
        ]);

        $view->assertSee('dashboard-kpi-card', false);
        $view->assertSee('Students', false);
        $view->assertSee('Teachers', false);
        $view->assertSee('Notice Board', false);
        $view->assertDontSee('data-testid="plan-usage-banner"', false);
        $view->assertDontSee('Freemium Plan', false);
        $view->assertDontSee('data-testid="dashboard-kpi-placeholder"', false);
        $view->assertDontSee('data-testid="setup-banner"', false);
    }

    public function test_profile_settings_shows_plan_info_line(): void
    {
        $plan = Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);
        CurrentPlan::create([
            'school_id' => $this->school->id,
            'plan_id' => $plan->id,
            'status' => 'running',
        ]);

        $this->actingAs($this->admin);
        $response = $this->get('/admin/editprofile');

        $response->assertOk();
        $response->assertSee('data-testid="profile-plan-info"', false);
        $response->assertSee('Current plan:', false);
        $response->assertSee('Freemium', false);
        $response->assertSee('/admin/subscriptions', false);
    }

    public function test_profile_settings_shows_no_plan_line_when_missing(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get('/admin/editprofile');

        $response->assertOk();
        $response->assertSee('data-testid="profile-plan-info"', false);
        $response->assertSee('No active plan.', false);
    }

    public function test_pre_academic_year_admin_can_open_edit_profile_for_plan_info(): void
    {
        // Re-enable MustBePrivilege — profile is allowlisted so plan can leave the dashboard.
        $this->withMiddleware(MustBePrivilege::class);

        $plan = Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);
        CurrentPlan::create([
            'school_id' => $this->school->id,
            'plan_id' => $plan->id,
            'status' => 'running',
        ]);

        $this->actingAs($this->admin);
        $response = $this->get('/admin/editprofile');

        $response->assertOk();
        $response->assertSee('data-testid="profile-plan-info"', false);
        $response->assertSee('Current plan:', false);
        $response->assertSee('Freemium', false);
    }
}
