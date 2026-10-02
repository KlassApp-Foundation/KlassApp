<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Logo is a real link named "KlassApp home":
 * - public/auth/legal/error pages → /
 * - logged-in dashboards → that role's dashboard home
 */
class LogoGoesHomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            \App\Http\Middleware\MustBePrivilege::class,
            \App\Http\Middleware\MustBeSchoolAdmin::class,
            \App\Http\Middleware\MustBeTeacher::class,
            \App\Http\Middleware\MustBeStudent::class,
        ]);

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_landing_logo_links_home_with_accessible_name(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        file_put_contents('/tmp/logo-base.txt', base_path()."
".view('landing-v2')->getPath()."
");
        $this->assertStringContainsString('aria-label="KlassApp home"', $response->getContent());
        $this->assertStringNotContainsString('aria-label="KlassApp"', $response->getContent());
        $this->assertMatchesRegularExpression(
            '/aria-label="KlassApp home"[^>]*>\s*<img[^>]*klassapp-logo-primary\.svg/s',
            $response->getContent()
        );
    }

    public function test_login_logo_links_home_with_accessible_name(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $this->assertStringContainsString('aria-label="KlassApp home"', $response->getContent());
        $this->assertStringContainsString('href="'.url('/').'"', $response->getContent());
    }

    public function test_register_logo_links_home_with_accessible_name(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $this->assertStringContainsString('aria-label="KlassApp home"', $response->getContent());
    }

    public function test_password_reset_logo_links_home_with_accessible_name(): void
    {
        $response = $this->get('/password/reset');

        $response->assertOk();
        $this->assertStringContainsString('aria-label="KlassApp home"', $response->getContent());
    }

    public function test_privacy_logo_links_home_with_accessible_name(): void
    {
        $response = $this->get('/privacy-policy');

        $response->assertOk();
        $this->assertStringContainsString('aria-label="KlassApp home"', $response->getContent());
    }

    public function test_error_page_logo_links_home_with_accessible_name(): void
    {
        $response = $this->get('/this-route-should-404-'.Str::random(8));

        $response->assertNotFound();
        $this->assertStringContainsString('aria-label="KlassApp home"', $response->getContent());
    }

    public function test_admin_dashboard_logo_links_dashboard_home(): void
    {
        $schoolId = $this->seedSchool();
        $admin = $this->makeUser(3, $schoolId);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $this->assertStringContainsString('aria-label="KlassApp home"', $response->getContent());
        $this->assertStringContainsString('href="'.route('dashboard').'"', $response->getContent());
    }

    public function test_teacher_dashboard_logo_links_teacher_dashboard(): void
    {
        $schoolId = $this->seedSchool();
        $teacher = $this->makeUser(5, $schoolId);

        $response = $this->actingAs($teacher)->get('/teacher/dashboard');

        $response->assertOk();
        $this->assertStringContainsString('aria-label="KlassApp home"', $response->getContent());
        $this->assertStringContainsString(
            'href="'.route('teacher.dashboard').'"',
            $response->getContent()
        );
    }

    private function seedSchool(): int
    {
        return (int) DB::table('schools')->insertGetId([
            'name' => 'Logo School '.Str::random(4),
            'slug' => 'logo-'.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeUser(int $usergroupId, int $schoolId): User
    {
        $user = User::factory()->create([
            'usergroup_id' => $usergroupId,
            'school_id' => $schoolId,
            'name' => 'Logo User '.$usergroupId,
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $schoolId,
            'usergroup_id' => $usergroupId,
            'firstname' => 'Logo',
            'lastname' => 'User',
        ]);

        return $user;
    }
}
