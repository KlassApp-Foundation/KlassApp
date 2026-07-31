<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImpersonateControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $otherAdmin;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin'],
            ['id' => 3, 'name' => 'schooladmin'],
            ['id' => 5, 'name' => 'teacher'],
        ]);

        $this->superadmin = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
            'email' => 'impersonate-superadmin@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $this->superadmin->id,
            'usergroup_id' => 1,
            'firstname' => 'Site',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => null,
            'email' => 'impersonate-admin@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Impersonate',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        $this->otherAdmin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => null,
            'email' => 'impersonate-other-admin@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $this->otherAdmin->id,
            'usergroup_id' => 3,
            'firstname' => 'Other',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        $this->teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => null,
            'email' => 'impersonate-teacher@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $this->teacher->id,
            'usergroup_id' => 5,
            'firstname' => 'Impersonate',
            'lastname' => 'Teacher',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function school_admin_impersonating_teacher_returns_to_admin_dashboard_on_stop(): void
    {
        // Auth::login stores the real user id in the session (actingAs does not).
        \Illuminate\Support\Facades\Auth::login($this->admin);

        $this->admin->setImpersonating($this->teacher->id);
        $this->assertTrue($this->admin->isImpersonating(), 'Impersonation must be active');

        $response = $this->get('/teacher/impersonate/stop');

        $response->assertRedirect('/admin/dashboard');
        $this->assertFalse($this->admin->isImpersonating(), 'Impersonation must be stopped');
    }

    /** @test */
    public function school_admin_impersonating_admin_redirects_to_admin_dashboard_not_superadmin(): void
    {
        \Illuminate\Support\Facades\Auth::login($this->admin);

        $this->admin->setImpersonating($this->otherAdmin->id);
        $this->assertTrue($this->admin->isImpersonating());

        $response = $this->get('/teacher/impersonate/stop');

        $response->assertRedirect('/admin/dashboard');
    }

    /** @test */
    public function superadmin_impersonating_school_admin_returns_to_superadmin_dashboard_on_stop(): void
    {
        \Illuminate\Support\Facades\Auth::login($this->superadmin);

        $this->superadmin->setImpersonating($this->admin->id);
        $this->assertTrue($this->superadmin->isImpersonating());

        $response = $this->get('/teacher/impersonate/stop');

        $response->assertRedirect('/superadmin/dashboard');
        $this->assertFalse($this->superadmin->isImpersonating());
    }
}
