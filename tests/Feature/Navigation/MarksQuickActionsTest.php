<?php

namespace Tests\Feature\Navigation;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\MustBeTeacher;
use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\User;
use App\States\Approval\Pending;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Enter marks has one address, the upcoming-exams tile does not 404,
 * mark submissions stay under Exams and reports, and promotion is a POST.
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
        $found = [];
        $approvalsChildren = null;
        $walk = function (array $rows, ?string $parent) use (&$walk, &$found, &$approvalsChildren): void {
            foreach ($rows as $row) {
                if (($row['label'] ?? '') === 'Mark submissions') {
                    $found[] = $parent;
                }
                if (($row['label'] ?? '') === 'Approvals') {
                    $approvalsChildren = $row['children'] ?? [];
                }
                if (! empty($row['children'])) {
                    $walk($row['children'], $row['label']);
                }
                if (! empty($row['rows'])) {
                    $walk($row['rows'], $parent);
                }
            }
        };
        $walk(config('navigation.roles.admin.sections'), null);

        $this->assertSame(['Exams and reports'], $found);
        $this->assertSame([], $approvalsChildren);

        DB::table('usergroups')->insert([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $admin = $this->user(3);
        $student = User::factory()->create([
            'school_id' => $admin->school_id,
            'usergroup_id' => 6,
            'status' => 'active',
        ]);
        $link = ParentLinkRequest::create([
            'school_id' => $admin->school_id,
            'phone' => '+256700444111',
            'parent_name' => 'Menu Parent',
            'child_name' => 'Menu Student',
            'child_class' => 'P5',
            'school_name' => 'Quick Actions School',
            'suggested_student_id' => $student->id,
            'candidate_student_ids' => [$student->id],
            'status' => 'pending',
        ]);
        Approval::create([
            'approvable_type' => ParentLinkRequest::class,
            'approvable_id' => $link->id,
            'state' => Pending::class,
            'requested_by' => null,
            'comments' => $link->summaryLine(),
        ]);

        $this->actingAs($admin);
        $html = view('layouts.partials.sidebar-menu', ['role' => 'admin'])->render();

        $this->assertSame(1, substr_count($html, '>Mark submissions</span>'));
        $this->assertMatchesRegularExpression(
            '/data-sidebar-count="approvals"(?![^>]*hidden)[^>]*>1</',
            $html
        );
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
