<?php

namespace Tests\Feature\Auth;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * /login while signed in must land on the signed-in person's own dashboard.
 * Never a 404, and never another account's session.
 */
class LoginWhileAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Login School', 'slug' => 'login-school', 'email' => 'l@t.ug',
            'phone' => '0700000011', 'status' => 1,
        ]);
    }

    private function makeUser(int $group, string $name): User
    {
        $this->counter++;

        return User::create([
            'school_id' => $this->school->id, 'usergroup_id' => $group, 'name' => $name,
            'email' => 'user'.$this->counter.'@login.test', 'password' => bcrypt('secret'),
            'status' => 'active', 'email_verified' => 1,
        ]);
    }

    public function test_a_signed_in_parent_visiting_login_lands_on_the_parent_dashboard(): void
    {
        $this->actingAs($this->makeUser(7, 'Parent One'))
            ->get('/login')
            ->assertRedirect('/parent/dashboard');
    }

    public function test_a_signed_in_teacher_visiting_login_lands_on_the_teacher_dashboard(): void
    {
        $this->actingAs($this->makeUser(5, 'Teacher One'))
            ->get('/login')
            ->assertRedirect('/teacher/dashboard');
    }

    public function test_posting_another_users_credentials_does_not_switch_the_session(): void
    {
        $parent = $this->makeUser(7, 'Parent Two');
        $teacher = $this->makeUser(5, 'Teacher Two');

        $this->actingAs($parent)
            ->post('/login', ['email' => $teacher->email, 'password' => 'secret'])
            ->assertRedirect('/parent/dashboard');

        $this->assertSame($parent->id, auth()->id(), 'the first session must survive');
    }

    public function test_an_intended_url_left_by_another_role_is_ignored(): void
    {
        $teacher = $this->makeUser(5, 'Teacher Three');

        // A guest (in the story, the parent's session) asks for an admin page;
        // the login redirect stores it as the intended URL.
        $this->get('/admin/students')->assertRedirect(route('login'));

        $this->post('/login', ['email' => $teacher->email, 'password' => 'secret'])
            ->assertRedirect('/teacher/dashboard');
    }

    public function test_an_intended_url_inside_the_users_own_area_is_kept(): void
    {
        $teacher = $this->makeUser(5, 'Teacher Four');

        $this->get('/teacher/dashboard')->assertRedirect(route('login'));

        $this->post('/login', ['email' => $teacher->email, 'password' => 'secret'])
            ->assertRedirect('/teacher/dashboard');
    }
}
