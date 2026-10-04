<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        \DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);
        \DB::table('settings')->insertOrIgnore([
            ['key' => 'login_status', 'value' => '1'],
        ]);
        \Config::set('settings.login_status', 1);
    }

    public function test_subject_teacher_login_redirects_to_teacher_dashboard(): void
    {
        $school = School::create([
            'name' => 'Teacher Redirect School',
            'email' => 'teacher-redirect@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'teacher-redirect-school',
            'status' => 1,
        ]);

        // A pre-gate account (created before the verified-email gate's UTC
        // cutoff, 2026-10-05): its unconfirmed email must not change where
        // signing in redirects, regardless of the wall clock or timezone.
        $teacher = User::create([
            'created_at' => '2026-09-15 09:00:00',
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => 'Subject Teacher',
            'email' => 'subject.teacher@test.sch.ug',
            'password' => Hash::make('password'),
            'email_verified' => 0,
            'status' => 'active',
            'is_reset' => 0,
        ]);

        $response = $this->post('/login', [
            'email' => $teacher->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/teacher/dashboard');
    }

    public function test_redirect_to_method_targets_teacher_dashboard_for_ug5(): void
    {
        $school = School::create([
            'name' => 'Teacher Redirect School 2',
            'email' => 'teacher-redirect2@test.sch.ug',
            'phone' => '0700000098',
            'slug' => 'teacher-redirect-school-2',
            'status' => 1,
        ]);
        $teacher = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'name' => 'Subject Teacher 2',
            'email' => 'subject.teacher2@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $this->actingAs($teacher);
        $controller = app(\App\Http\Controllers\Auth\LoginController::class);
        $this->assertSame('/teacher/dashboard', $controller->redirectTo());
    }
}
