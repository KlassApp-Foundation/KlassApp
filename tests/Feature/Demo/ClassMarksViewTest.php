<?php

namespace Tests\Feature\Demo;

use App\Models\User;
use App\Models\Userprofile;
use App\Services\DemoSchoolCommsGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Demo\Concerns\BuildsFollowAlongSchools;
use Tests\TestCase;

/**
 * Task 9 demo data: the read-only class marks view (students by code, a row
 * per subject with the grade under the mark), reached from the dashboard's
 * "Performance by class" card.
 */
class ClassMarksViewTest extends TestCase
{
    use BuildsFollowAlongSchools;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config(['app.timezone' => 'Pacific/Auckland', 'dashboard.v2_enabled' => true]);
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        DemoSchoolCommsGuard::flushCache();
        $this->buildSchools();
    }

    private function admin(string $which): User
    {
        $school = $this->fx[$which]['school'];
        $admin = User::create(['school_id' => $school->id, 'usergroup_id' => 3, 'name' => 'Admin ' . $which, 'email' => 'admin-' . $which . '@example.com', 'password' => bcrypt('x'), 'status' => 'active', 'email_verified' => 1]);
        Userprofile::create(['user_id' => $admin->id, 'school_id' => $school->id, 'usergroup_id' => 3, 'firstname' => 'Admin', 'lastname' => ucfirst($which), 'status' => 'active']);

        return $admin;
    }

    public function test_task9_class_marks_view_lists_students_by_code_with_four_subjects_and_grades(): void
    {
        $response = $this->actingAs($this->admin('demo'))->get('/admin/marks/classes');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertSame(3, substr_count($html, 'data-testid="class-marks-student"'));
        $response->assertSee('End of Term Examination · Term III');
        $response->assertSeeInOrder(['Mathematics', 'Science', 'English', 'Social Studies']);
        $amina = $this->fx['demo']['students'][0];
        $response->assertSeeInOrder([$amina['code'], 'Amina', 'Mathematics', '<b>78</b><span>C3</span>', 'Science', '<b>64</b><span>C5</span>', 'English', '<b>91</b><span>D2</span>', 'Social Studies', '<b>55</b><span>C6</span>'], false);
        // Read only: no form and no older exam marks.
        $this->assertStringNotContainsString('<form', substr($html, strpos($html, 'data-testid="class-marks"')));
        $response->assertDontSee('<b>40</b>', false);
    }

    public function test_task9_class_marks_view_is_scoped_to_the_school(): void
    {
        $this->actingAs($this->admin('demo'))
            ->get('/admin/marks/classes/' . $this->fx['real']['section']->id)
            ->assertNotFound();
    }

    public function test_task9_performance_by_class_links_to_the_class_marks_view(): void
    {
        $response = $this->actingAs($this->admin('demo'))->get('/admin/dashboard?v2=1');

        $response->assertOk();
        $response->assertSee('data-testid="dashboard-v2-view-class-marks"', false);
        $response->assertSee('href="' . route('admin.marks.classes') . '"', false);
    }
}
