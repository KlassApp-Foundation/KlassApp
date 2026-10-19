<?php

namespace Tests\Feature\Navigation;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\MustBeTeacher;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Enter marks has one address, the upcoming-exams tile does not 404,
 * mark submissions sit under Approvals, and promotion is a POST.
 */
class MarksQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            MustBeSchoolAdmin::class,
            MustBePrivilege::class,
            MustBeTeacher::class,
        ]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_upcoming_exams_and_the_old_exam_list_open_the_marks_list(): void
    {
        $dashboard = file_get_contents(resource_path('views/teacher/dashboard/dashboard.blade.php'));
        $this->assertStringNotContainsString("url('/teacher/exams')", $dashboard);
        $this->assertStringContainsString("route('teacher.exam.marks')", $dashboard);

        $teacher = $this->user(5);
        $this->actingAs($teacher)
            ->get('/teacher/exams')
            ->assertRedirect(route('teacher.exam.marks'));
        $this->actingAs($teacher)
            ->get('/teacher/exam')
            ->assertRedirect(route('teacher.exam.marks'));
    }

    public function test_approvals_menu_includes_mark_submissions(): void
    {
        $nav = file_get_contents(base_path('config/navigation.php'));
        $this->assertStringContainsString("'Mark submissions'", $nav);
        $this->assertStringContainsString("'admin/marks/submissions'", $nav);
    }

    public function test_promotion_is_posted_and_a_get_does_not_promote(): void
    {
        $form = file_get_contents(resource_path('views/admin/marks/promotion.blade.php'));
        $this->assertStringContainsString('method="POST"', $form);
        $this->assertStringContainsString('@csrf', $form);
        $this->assertStringNotContainsString('method="GET"', $form);

        $this->actingAs($this->user(3))
            ->get('/admin/promotion')
            ->assertStatus(405);
    }

    private function user(int $group): User
    {
        $school = School::create([
            'name' => 'Quick Actions School '.$group,
            'email' => 'quick-'.$group.'-'.uniqid().'@t.sch.ug',
            'phone' => '070000008'.$group,
            'slug' => 'quick-actions-'.$group.'-'.uniqid(),
            'status' => 1,
        ]);

        return User::factory()->create([
            'usergroup_id' => $group,
            'school_id' => $school->id,
            'email' => 'user-'.$group.'-'.uniqid().'@t.sch.ug',
            'status' => 'active',
        ]);
    }
}
