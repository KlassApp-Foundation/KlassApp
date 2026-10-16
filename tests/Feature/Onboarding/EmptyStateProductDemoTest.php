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

    public function test_pre_onboarding_dashboard_is_v2_without_the_product_demo(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('data-testid="dashboard-v2-shell"', false);
        $response->assertSee('data-testid="dashboard-v2-empty-students"', false);
        $response->assertDontSee('data-testid="empty-state-product-demo"', false);
        $response->assertDontSee('js/empty-state-product-demo.js', false);
        $response->assertDontSee('data-testid="dashboard-kpi-grid"', false);
        $response->assertDontSee('data-testid="setup-banner"', false);
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
        $this->assertFileDoesNotExist(resource_path('views/admin/dashboard/dashboard.blade.php'));
        $v2 = file_get_contents(resource_path('views/admin/dashboard/dashboard_v2.blade.php'));
        $this->assertStringNotContainsString('empty-state-product-demo', $v2);
        $this->assertStringContainsString('data-testid="dashboard-v2-shell"', $v2);

    }

}
