<?php

namespace Tests\Feature\Security;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Services\Marks\MarksSubmissionService;
use App\Services\MarksImport\MarksImportService;
use App\Services\MarksRelease\MarksReleaseService;
use App\Support\SchoolAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Head teacher (SchoolSubadmin, ug4) has school-admin powers inside their own
 * school — settings, marks, exam sets, people, reports — and none in another.
 */
class HeadTeacherParityTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $headA;

    private User $headB;

    private User $teacher;

    private Section $sectionA;

    private Exam $examA;

    private Exam $examB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'SiteAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'SchoolSubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('exam_types')->insert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0,
                'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = School::create(['name' => 'Head School A', 'slug' => 'head-a', 'email' => 'a@head.test', 'phone' => '+256700000041', 'status' => 1]);
        $this->schoolB = School::create(['name' => 'Head School B', 'slug' => 'head-b', 'email' => 'b@head.test', 'phone' => '+256700000042', 'status' => 1]);

        foreach ([$this->schoolA, $this->schoolB] as $school) {
            $year = AcademicYear::create([
                'school_id' => $school->id, 'name' => '2026',
                'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
            ]);
            AcademicTerm::create([
                'school_id' => $school->id, 'academic_year_id' => $year->id,
                'name' => 'Term 1', 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30', 'status' => 'current',
            ]);
        }

        $this->headA = User::factory()->create(['school_id' => $this->schoolA->id, 'usergroup_id' => 4, 'email' => 'head.a@head.test', 'name' => 'Head A', 'status' => 'active']);
        $this->headB = User::factory()->create(['school_id' => $this->schoolB->id, 'usergroup_id' => 4, 'email' => 'head.b@head.test', 'name' => 'Head B', 'status' => 'active']);
        $this->teacher = User::factory()->create(['school_id' => $this->schoolA->id, 'usergroup_id' => 5, 'email' => 't@head.test', 'name' => 'Teacher', 'status' => 'active']);

        $yearA = AcademicYear::where('school_id', $this->schoolA->id)->firstOrFail();
        $this->sectionA = Section::create(['school_id' => $this->schoolA->id, 'name' => 'P.1', 'status' => 1]);
        $standard = Standard::create(['school_id' => $this->schoolA->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        StandardLink::create([
            'school_id' => $this->schoolA->id, 'academic_year_id' => $yearA->id,
            'standard_id' => $standard->id, 'section_id' => $this->sectionA->id, 'status' => 1,
        ]);
        $subject = Subject::create([
            'school_id' => $this->schoolA->id, 'academic_year_id' => $yearA->id,
            'standard_id' => $standard->id, 'section_id' => $this->sectionA->id,
            'name' => 'Mathematics', 'type' => 'core',
        ]);
        $this->examA = Exam::create([
            'school_id' => $this->schoolA->id, 'academic_year_id' => $yearA->id,
            'academic_term_id' => AcademicTerm::where('school_id', $this->schoolA->id)->value('id'),
            'standard_id' => $standard->id, 'section_id' => $this->sectionA->id,
            'subject_id' => $subject->id, 'teacher_id' => $this->teacher->id, 'exam_type_id' => 1, 'status' => 'undone',
        ]);

        $yearB = AcademicYear::where('school_id', $this->schoolB->id)->firstOrFail();
        $sectionB = Section::create(['school_id' => $this->schoolB->id, 'name' => 'P.1', 'status' => 1]);
        $standardB = Standard::create(['school_id' => $this->schoolB->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $subjectB = Subject::create([
            'school_id' => $this->schoolB->id, 'academic_year_id' => $yearB->id,
            'standard_id' => $standardB->id, 'section_id' => $sectionB->id,
            'name' => 'Mathematics', 'type' => 'core',
        ]);
        $this->examB = Exam::create([
            'school_id' => $this->schoolB->id, 'academic_year_id' => $yearB->id,
            'academic_term_id' => AcademicTerm::where('school_id', $this->schoolB->id)->value('id'),
            'standard_id' => $standardB->id, 'section_id' => $sectionB->id,
            'subject_id' => $subjectB->id, 'teacher_id' => $this->headB->id, 'exam_type_id' => 1, 'status' => 'undone',
        ]);
    }

    public function test_the_shared_helper_knows_who_manages_a_school(): void
    {
        $this->assertTrue(SchoolAdmin::is($this->headA));
        $this->assertTrue(SchoolAdmin::is(User::factory()->make(['usergroup_id' => 3])));
        $this->assertFalse(SchoolAdmin::is($this->teacher));
        $this->assertFalse(SchoolAdmin::is(null));
        $this->assertTrue(SchoolAdmin::managesOwnSchool($this->headA, $this->schoolA->id));
        $this->assertFalse(SchoolAdmin::managesOwnSchool($this->headA, $this->schoolB->id));
        $this->assertFalse(SchoolAdmin::managesOwnSchool($this->teacher, $this->schoolA->id));
        $this->assertTrue(SchoolAdmin::managesSchool(User::factory()->make(['usergroup_id' => 1]), $this->schoolB->id));
        $this->assertFalse(SchoolAdmin::managesSchool($this->headA, $this->schoolB->id));
    }

    public function test_admin_gates_allow_a_head_teacher_in_their_school_only(): void
    {
        $own = (object) ['school_id' => $this->schoolA->id, 'created_by' => 0];
        $other = (object) ['school_id' => $this->schoolB->id, 'created_by' => 0];

        foreach (['event-destroy', 'homework-manage', 'post-destroy', 'teacher-leave-manage', 'parent-link-request-manage'] as $gate) {
            $this->assertTrue(Gate::forUser($this->headA)->allows($gate, $own), $gate);
            $this->assertFalse(Gate::forUser($this->headA)->allows($gate, $other), $gate);
            $this->assertFalse(Gate::forUser($this->teacher)->allows($gate, $own), $gate);
        }
    }

    public function test_a_head_teacher_passes_the_full_school_admin_gate(): void
    {
        $this->assertTrue(Gate::forUser($this->headA)->allows('full-school-admin'));
        $this->assertFalse(Gate::forUser($this->teacher)->allows('full-school-admin'));
    }

    public function test_a_head_teacher_can_open_settings_for_their_own_school(): void
    {
        $this->actingAs($this->headA)->get('/admin/settings')->assertOk();
    }

    public function test_a_head_teacher_can_approve_marks_in_their_school_only(): void
    {
        ExamMarksSubmission::create([
            'school_id' => $this->schoolA->id, 'exam_id' => $this->examA->id,
            'class_id' => $this->sectionA->id, 'subject_id' => $this->examA->subject_id,
            'teacher_id' => $this->teacher->id, 'status' => 'submitted', 'approval_status' => 'pending', 'submitted_at' => now(),
        ]);

        $this->assertSame(1, app(MarksSubmissionService::class)->approve($this->headA, $this->examA));

        $this->expectException(HttpException::class);
        app(MarksSubmissionService::class)->approve($this->headA, $this->examB);
    }

    public function test_a_head_teacher_may_import_marks_in_their_school_only(): void
    {
        $imports = app(MarksImportService::class);

        $this->assertTrue($imports->canImport($this->headA, $this->examA));
        $this->assertFalse($imports->canImport($this->headA, $this->examB));
    }

    public function test_a_head_teacher_may_release_only_their_own_classes(): void
    {
        $releases = app(MarksReleaseService::class);

        $this->assertTrue($releases->isSchoolAdmin($this->headA));
        $this->assertTrue($releases->isSchoolAdmin($this->headB));
        $this->assertTrue($releases->links($this->headA)->every(fn ($link) => (int) $link->school_id === $this->schoolA->id));
        $this->assertTrue($releases->links($this->headB)->every(fn ($link) => (int) $link->school_id === $this->schoolB->id));
    }
}
