<?php

namespace Tests\Feature\Teacher;

use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private School $school;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Teacher Dashboard School',
            'email' => 'teacher-dash@test.sch.ug',
            'phone' => '0700000777',
            'slug' => 'teacher-dashboard-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => (string) now()->year,
            'description' => 'AY',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'status' => 1,
        ]);

        $this->teacher = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Test Teacher',
            'email' => 'teacher@teacher-dash.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacher->id,
            'usergroup_id' => 5,
            'firstname' => 'Test',
            'lastname' => 'Teacher',
        ]);
    }

    public function test_dashboard_page_loads(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('dashboard-shell--teacher');
        $response->assertSee('dashboard-kpi-grid');
    }

    public function test_dashboard_shows_greeting(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('dashboard-greeting');
        $response->assertSee('Teacher', false);
    }

    public function test_dashboard_shows_quick_actions(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('teacher-quick-actions');
        $response->assertSee('Take Attendance');
        $response->assertSee('Enter Marks');
        $response->assertSee('Post Homework');
    }

    public function test_dashboard_shows_kpi_cards(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('My Students');
        $response->assertSee('My Classes');
        $response->assertSee('Upcoming Exams');
        $response->assertSee('WhatsApp Linked');
        $response->assertSee('Marks needing you');
    }

    public function test_dashboard_shows_marks_entry_banner(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('teacher-marks-entry');
        $response->assertSee('Marks & corrections');
    }

    public function test_dashboard_shows_timetable_section(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee("Today's Schedule", false);
    }

    public function test_dashboard_shows_noticeboard_section(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Notice Board');
    }

    public function test_dashboard_shows_recent_activity_section(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Recent Activity');
    }

    public function test_dashboard_shows_pending_approvals_section(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Pending Approvals');
    }

    public function test_dashboard_shows_upcoming_deadlines_section(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Upcoming Deadlines');
    }

    public function test_dashboard_shows_empty_state_when_no_classes(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee("You haven't been assigned any classes yet", false);
    }

    public function test_dashboard_shows_no_classes_scheduled_for_today(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('No classes scheduled for today.');
    }

    public function test_dashboard_shows_no_notices_published_yet(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('No notices published yet.');
    }

    public function test_dashboard_shows_no_recent_activity(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('No recent activity.');
    }

    public function test_dashboard_shows_no_pending_approvals(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('No pending approvals.');
    }

    public function test_dashboard_shows_no_upcoming_deadlines(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('No upcoming deadlines.');
    }

    public function test_dashboard_scopes_data_by_school(): void
    {
        $otherSchool = School::create([
            'name' => 'Other School',
            'email' => 'other@test.sch.ug',
            'phone' => '0700000888',
            'slug' => 'other-school',
            'status' => 1,
        ]);

        $otherTeacher = User::create([
            'school_id' => $otherSchool->id,
            'usergroup_id' => 5,
            'name' => 'Other Teacher',
            'email' => 'other@other.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee($otherTeacher->name);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/teacher/dashboard');

        $response->assertRedirect('/login');
    }
}
