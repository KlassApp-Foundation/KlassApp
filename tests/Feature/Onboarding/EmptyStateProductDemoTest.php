<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmptyStateProductDemoTest extends TestCase
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
            'name' => "Demo's School",
            'email' => 'demo@empty.sch.ug',
            'phone' => '0700000077',
            'slug' => 'demo-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Demo Admin',
            'email' => 'admin@demo.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Demo',
            'lastname' => 'Admin',
        ]);
    }

    public function test_pre_onboarding_dashboard_renders_product_demo_markup(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('data-testid="empty-state-product-demo"', false);
        $response->assertSee('data-testid="es-demo-scene-whatsapp"', false);
        $response->assertSee('data-testid="es-demo-scene-toshi"', false);
        $response->assertSee('data-testid="es-demo-scene-connectors"', false);
        $response->assertSee('data-testid="es-demo-connectors-disclaimer"', false);
        $response->assertSee('Coming soon', false);
        $response->assertSee('aspirational', false);
        $response->assertSee('js/empty-state-product-demo.js', false);
        $response->assertSee('data-testid="setup-banner"', false);

        // Demo appears before setup banner in the HTML (top half).
        $html = $response->getContent();
        $demoPos = strpos($html, 'data-testid="empty-state-product-demo"');
        $bannerPos = strpos($html, 'data-testid="setup-banner"');
        $this->assertNotFalse($demoPos);
        $this->assertNotFalse($bannerPos);
        $this->assertLessThan($bannerPos, $demoPos);
    }

    public function test_product_demo_js_and_reduced_motion_css_are_present(): void
    {
        $jsPath = public_path('js/empty-state-product-demo.js');
        $this->assertFileExists($jsPath);
        $js = file_get_contents($jsPath);
        $this->assertStringContainsString('prefers-reduced-motion', $js);
        $this->assertStringContainsString('es-demo--reduced', $js);

        $css = file_get_contents(public_path('css/empty-state-product-demo.css'));
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('.es-demo', $css);

        // Syntax check — node -c exits 0 when parseable.
        $exit = 0;
        $out = [];
        exec('node --check '.escapeshellarg($jsPath).' 2>&1', $out, $exit);
        $this->assertSame(0, $exit, implode("\n", $out));
    }

    public function test_post_onboarding_dashboard_does_not_include_product_demo(): void
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

        $view->assertDontSee('data-testid="empty-state-product-demo"', false);
        $view->assertDontSee('empty-state-product-demo.js', false);
        $view->assertSee('dashboard-kpi-card', false);
    }
}
