<?php

namespace Tests\Feature\Auth;

use App\Models\School;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ParentMagicLoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentWebAuthShellTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create(['name' => 'Shell School', 'email' => 'shell@school.ug', 'status' => 1]);

        $this->parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
            'email' => 'parent.shell@test.ug',
            'password' => bcrypt('shell-pass-123'),
        ]);

        Userprofile::create([
            'user_id' => $this->parent->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'Shell',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);

        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
        ]);

        StudentParentLink::create([
            'school_id' => $school->id,
            'parent_id' => $this->parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);
    }

    public function test_parent_password_login_redirects_to_parent_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'parent.shell@test.ug',
            'password' => 'shell-pass-123',
        ]);

        $response->assertRedirect('/parent/dashboard');
        $this->assertAuthenticatedAs($this->parent);
    }

    public function test_parent_reaches_dashboard_via_magic_link_with_shell_layout(): void
    {
        $url = app(ParentMagicLoginService::class)->issueLinkForPhone('+256700999888', $this->parent);

        $response = $this->get($url);

        $response->assertRedirect(route('parent.dashboard'));

        $dashboard = $this->followingRedirects()->get(route('parent.dashboard'));

        $dashboard->assertOk();
        $dashboard->assertSee('Parent Portal');
        $dashboard->assertSee('Dashboard');
        $dashboard->assertSee('Children');
    }

    public function test_teacher_cannot_access_parent_dashboard(): void
    {
        $teacher = User::factory()->create([
            'school_id' => 1,
            'usergroup_id' => 5,
            'status' => 'active',
        ]);

        $this->actingAs($teacher)
            ->get(route('parent.dashboard'))
            ->assertRedirect('/teacher/dashboard');
    }

    public function test_homebanner_dashboard_link_targets_parent_portal_for_ug7(): void
    {
        $this->actingAs($this->parent);

        $html = view('welcome.homebanner')->render();

        $this->assertStringContainsString('/parent/dashboard', $html);
        $this->assertStringNotContainsString('/admin/dashboard', $html);
    }

    public function test_checkstatus_allows_ug7_parent_with_active_links(): void
    {
        $response = $this->post('/login', [
            'email' => 'parent.shell@test.ug',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionDoesntHaveErrors([
            'email' => 'Invalid Credentials',
            'password' => 'Invalid Credentials.You are not in this school',
        ]);
    }
}
