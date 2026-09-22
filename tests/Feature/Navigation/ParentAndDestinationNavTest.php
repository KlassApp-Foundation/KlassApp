<?php

namespace Tests\Feature\Navigation;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Services\Parent\ParentPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Capability-matrix nav findings.
 *
 * Item 1: the parent nav had 2 items against real per-child fees/grades/attendance routes.
 * Item 4: wrong and duplicate destinations (admin Health, teacher Exams/Marks, teacher
 *         Students duplicating Classes).
 *
 * The parent resolver is exercised here through a stub binding, so the three shapes
 * (one child, several children, none) are deterministic. The real portal service is
 * exercised by the parent browser pass.
 */
class ParentAndDestinationNavTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private function fakeChildren(array $children): void
    {
        $stub = new class($children)
        {
            public function __construct(private array $rows) {}

            public function listChildren($user): array
            {
                return ['success' => true, 'children' => $this->rows, 'count' => count($this->rows), 'grouped_by_school' => []];
            }
        };

        $this->instance(ParentPortalService::class, $stub);
    }

    private function parentNavHtml(): string
    {
        $parent = User::factory()->create(['usergroup_id' => 7, 'school_id' => 1, 'email' => 'navparent'.uniqid().'@t.sch.ug']);
        $this->actingAs($parent);

        return view('layouts.partials.sidebar-menu', ['role' => 'parent'])->render();
    }

    public function test_single_child_parent_gets_direct_per_child_links(): void
    {
        $this->fakeChildren([['student_id' => 4242, 'name' => 'Only Child', 'school_id' => 1]]);

        $html = $this->parentNavHtml();

        // the real per-child URIs live under /parent/children/{student}/...
        $this->assertStringContainsString(route('parent.children.fees', 4242), $html);
        $this->assertStringContainsString(route('parent.children.grades', 4242), $html);
        $this->assertStringContainsString(route('parent.children.attendance', 4242), $html);
    }

    public function test_multi_child_parent_lands_on_the_children_page(): void
    {
        $this->fakeChildren([
            ['student_id' => 11, 'name' => 'First', 'school_id' => 1],
            ['student_id' => 12, 'name' => 'Second', 'school_id' => 1],
        ]);

        $html = $this->parentNavHtml();

        $this->assertStringNotContainsString(route('parent.children.fees', 11), $html);
        $this->assertStringNotContainsString(route('parent.children.fees', 12), $html);
        // all three entries fall back to the picker
        $this->assertGreaterThanOrEqual(3, substr_count($html, url('/parent/children')));
    }

    public function test_childless_parent_still_renders_the_entries_safely(): void
    {
        $this->fakeChildren([]);

        $html = $this->parentNavHtml();

        $this->assertStringContainsString('Fees', $html);
        $this->assertStringContainsString(url('/parent/children'), $html);
    }

    public function test_admin_health_points_at_the_real_health_destination(): void
    {
        $admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => 1, 'email' => 'navadmin'.uniqid().'@t.sch.ug']);
        $this->actingAs($admin);

        $html = view('layouts.partials.sidebar-menu', ['role' => 'admin'])->render();

        // /admin/health is the dedicated landing; /admin/student/health/{userId} is per-student
        $this->assertStringContainsString(url('/admin/health'), $html);
    }

    private function teacherSidebar(User $teacher): string
    {
        $this->actingAs($teacher);

        return view('layouts.partials.sidebar-menu', ['role' => 'teacher'])->render();
    }

    private function schoolWithClassTeacherAndPeer(): array
    {
        Cache::flush();
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Dest Nav School', 'slug' => 'dest-nav-'.uniqid(),
            'email' => 'dn-'.uniqid().'@t.sch.ug', 'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => (string) now()->year, 'description' => 'AY',
            'start_date' => now()->subMonths(2)->startOfDay(), 'end_date' => now()->addMonths(6)->endOfDay(), 'status' => 1,
        ]);
        Cache::flush();

        $ct = User::factory()->create(['usergroup_id' => 5, 'school_id' => $school->id, 'email' => 'ct'.uniqid().'@t.sch.ug']);
        $peer = User::factory()->create(['usergroup_id' => 5, 'school_id' => $school->id, 'email' => 'peer'.uniqid().'@t.sch.ug']);

        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $s1 = Section::create(['school_id' => $school->id, 'name' => 'DN P7', 'status' => 1]);
        $s2 = Section::create(['school_id' => $school->id, 'name' => 'DN P6', 'status' => 1]);
        StandardLink::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id, 'section_id' => $s1->id, 'class_teacher_id' => $ct->id, 'stream' => 'A', 'status' => 1]);
        $other = StandardLink::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id, 'section_id' => $s2->id, 'class_teacher_id' => $peer->id, 'stream' => 'A', 'status' => 1]);

        $subject = Subject::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id, 'section_id' => $s2->id, 'name' => 'Science', 'status' => 1]);
        Teacherlink::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'standardLink_id' => $other->id, 'subject_id' => $subject->id, 'teacher_id' => $ct->id]);

        return [$ct, $peer, $school];
    }

    public function test_teacher_exams_and_marks_are_distinct_destinations(): void
    {
        [$ct] = $this->schoolWithClassTeacherAndPeer();

        $html = $this->teacherSidebar($ct);

        $this->assertStringContainsString(route('teacher.exams.create'), $html);
        $this->assertStringContainsString(route('teacher.exam.marks'), $html);
        $this->assertStringNotContainsString(url('/teacher/exams'), $html);
    }

    public function test_teacher_exams_is_class_teacher_conditioned_matching_its_controller(): void
    {
        [$ct, $peer, $school] = $this->schoolWithClassTeacherAndPeer();

        // $peer homerooms a class in this fixture, so build a teacher with no homeroom at all.
        $plain = User::factory()->create(['usergroup_id' => 5, 'school_id' => $school->id, 'email' => 'plain'.uniqid().'@t.sch.ug']);
        $plainHtml = $this->teacherSidebar($plain);

        $this->assertStringNotContainsString(route('teacher.exams.create'), $plainHtml, 'exam create is class-teacher scoped, so the link must hide');
        $this->assertStringContainsString(route('teacher.exam.marks'), $plainHtml, 'marks entry stays available to any teacher');
    }

    public function test_teacher_students_duplicate_entry_is_gone(): void
    {
        [$ct] = $this->schoolWithClassTeacherAndPeer();

        $html = $this->teacherSidebar($ct);

        // "Students" used to resolve to the same destination as "Classes"
        $this->assertStringNotContainsString('>Students<', str_replace(['\n', '  '], '', $html));
    }
}
