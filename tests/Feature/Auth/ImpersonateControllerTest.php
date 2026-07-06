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

    private User $admin;
    private User $otherAdmin;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        // Seed usergroups
        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin'],
            ['id' => 3, 'name' => 'schooladmin'],
            ['id' => 5, 'name' => 'teacher'],
        ]);

        // Create a school admin (will be the impersonator)
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

        // Create another school admin (to be impersonated — this tests 
        // the usergroup_id=3 branch where the old bug redirected to /superadmin/dashboard)
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

        // Create a teacher (will be impersonated)
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
    public function school_admin_impersonating_teacher_redirects_to_teacher_dashboard_on_stop(): void
    {
        $this->actingAs($this->admin);

        // Start impersonating the teacher
        $this->admin->setImpersonating($this->teacher->id);
        $this->assertTrue($this->admin->isImpersonating(), 'Impersonation must be active');

        // Stop impersonation
        $response = $this->get('/teacher/impersonate/stop');

        // The original code reads Auth::user()->id BEFORE stopImpersonating(),
        // which returns the impersonated user's ID (teacher), so $user is the teacher.
        // Redirect goes to the impersonated user's dashboard.
        $response->assertRedirect('/teacher/dashboard');
        $this->assertFalse($this->admin->isImpersonating(), 'Impersonation must be stopped');
    }

    /** @test */
    public function school_admin_impersonating_admin_redirects_to_admin_dashboard_not_superadmin(): void
    {
        $this->actingAs($this->admin);

        // Impersonate another school admin (same usergroup_id)
        $this->admin->setImpersonating($this->otherAdmin->id);
        $this->assertTrue($this->admin->isImpersonating());

        $response = $this->get('/teacher/impersonate/stop');

        // $user will be the impersonated admin (usergroup_id=3).
        // The OLD code would redirect to /superadmin/dashboard (bug).
        // The FIX redirects to /admin/dashboard.
        $response->assertRedirect('/admin/dashboard');
    }

}
