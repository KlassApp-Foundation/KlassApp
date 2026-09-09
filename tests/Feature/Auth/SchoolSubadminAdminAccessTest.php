<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\MustBeFullSchoolAdmin;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class SchoolSubadminAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'SiteAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'SchoolSubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('schools')->insert([
            'id' => 1,
            'name' => 'Subadmin Test School',
            'slug' => 'subadmin-test-school',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function userWithGroup(int $usergroupId): User
    {
        return User::factory()->create([
            'usergroup_id' => $usergroupId,
            'school_id' => 1,
            'status' => 'active',
        ]);
    }

    private function runSchoolAdmin(User $user)
    {
        Auth::login($user);
        $request = Request::create('/admin/students', 'GET');

        return (new MustBeSchoolAdmin)->handle($request, fn () => response('ok', 200));
    }

    private function runFullSchoolAdmin(User $user)
    {
        Auth::login($user);
        $request = Request::create('/admin/settings', 'GET');

        return (new MustBeFullSchoolAdmin)->handle($request, fn () => response('ok', 200));
    }

    public function test_school_subadmin_passes_must_be_school_admin(): void
    {
        $response = $this->runSchoolAdmin($this->userWithGroup(4));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_school_admin_passes_must_be_school_admin(): void
    {
        $response = $this->runSchoolAdmin($this->userWithGroup(3));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_teacher_is_redirected_from_must_be_school_admin(): void
    {
        $response = $this->runSchoolAdmin($this->userWithGroup(5));

        $this->assertTrue($response->isRedirect());
        $this->assertStringEndsWith('/teacher/dashboard', $response->headers->get('Location'));
    }

    public function test_school_subadmin_is_blocked_from_full_school_admin_settings(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->runFullSchoolAdmin($this->userWithGroup(4));
    }

    public function test_school_admin_passes_full_school_admin_settings(): void
    {
        $response = $this->runFullSchoolAdmin($this->userWithGroup(3));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_login_redirects_school_subadmin_to_subadmin_dashboard(): void
    {
        $user = $this->userWithGroup(4);
        $user->email = 'deputy.admin@test.ug';
        $user->password = bcrypt('deputy-pass-123');
        $user->save();

        $response = $this->post('/login', [
            'email' => 'deputy.admin@test.ug',
            'password' => 'deputy-pass-123',
        ]);

        $response->assertRedirect('/subadmin/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}
