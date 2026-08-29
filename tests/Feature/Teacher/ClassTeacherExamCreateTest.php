<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PR-B: class-teacher exam create/edit (class-scoped), additive with subject-teacher marks access.
 */
class ClassTeacherExamCreateTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $classTeacher;

    private User $subjectTeacher;

    private User $peerTeacher;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Section $section;

    private Section $otherSection;

    private Standard $standard;

    private Subject $subject;

    private ExamType $examType;

    private StandardLink $stream;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeTeacher::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'CT Exam Create School',
            'slug' => 'ct-exam-create-' . uniqid(),
            'email' => 'ct-exam-create-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'name' => 'School Admin',
            'email' => 'admin.ctcreate@t.sch.ug',
        ]);

        $this->classTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Class Teacher',
            'email' => 'class.ctcreate@t.sch.ug',
        ]);

        $this->subjectTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Subject Teacher',
            'email' => 'subject.ctcreate@t.sch.ug',
        ]);

        $this->peerTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Peer Teacher',
            'email' => 'peer.ctcreate@t.sch.ug',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => (string) now()->year,
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'First Term',
            'status' => 'current',
            'starts_on' => now()->startOfYear(),
            'ends_on' => now()->startOfYear()->addMonths(3),
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.7',
            'status' => 1,
        ]);

        $this->otherSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.6',
            'status' => 1,
        ]);

        $this->standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Mathematics',
            'type' => 'core',
            'status' => 1,
        ]);

        Subject::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->otherSection->id,
            'academic_year_id' => $this->year->id,
            'name' => 'English',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->examType = ExamType::create([
            'name' => 'Mid Term',
            'code' => 'MID',
            'contributes_to_report_total' => true,
        ]);

        $this->stream = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'class_teacher_id' => $this->classTeacher->id,
            'stream' => 'A',
            'status' => 1,
        ]);

        Teacherlink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->stream->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->subjectTeacher->id,
        ]);
    }

    public function test_class_teacher_can_create_exam_for_own_class(): void
    {
        $response = $this->actingAs($this->classTeacher)->post(route('teacher.exams.store'), [
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $this->examType->id,
            // teacher_id omitted — should default from Teacherlink → subject teacher
        ]);

        $response->assertRedirect(route('teacher.exam.marks'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('exams', [
            'school_id' => $this->school->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->subjectTeacher->id,
            'exam_type_id' => $this->examType->id,
            'status' => 'undone',
        ]);
    }

    public function test_class_teacher_cannot_create_exam_for_other_class(): void
    {
        $otherSubject = Subject::where('section_id', $this->otherSection->id)->firstOrFail();

        $response = $this->actingAs($this->classTeacher)->post(route('teacher.exams.store'), [
            'section_id' => $this->otherSection->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $otherSubject->id,
            'exam_type_id' => $this->examType->id,
            'teacher_id' => $this->classTeacher->id,
        ]);

        $response->assertForbidden();
        $this->assertSame(0, Exam::where('school_id', $this->school->id)->count());
    }

    public function test_exam_assigned_to_subject_teacher_appears_in_their_marks_list(): void
    {
        $this->actingAs($this->classTeacher)->post(route('teacher.exams.store'), [
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $this->examType->id,
            'teacher_id' => $this->subjectTeacher->id,
        ])->assertRedirect(route('teacher.exam.marks'));

        $exam = Exam::where('school_id', $this->school->id)->firstOrFail();
        $this->assertSame((int) $this->subjectTeacher->id, (int) $exam->teacher_id);

        $list = $this->actingAs($this->subjectTeacher)->get(route('teacher.exam.marks'));
        $list->assertOk();
        $list->assertSee('MATHEMATICS', false);
        $list->assertSee(route('teacher.exam.marks.enter', $exam), false);

        $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.exam.marks.enter', $exam))
            ->assertOk();
    }

    public function test_admin_exam_create_flow_unaffected(): void
    {
        $before = Exam::where('school_id', $this->school->id)->count();

        $response = $this->actingAs($this->admin)->post(route('admin.exams.store'), [
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->peerTeacher->id,
            'exam_type_id' => $this->examType->id,
        ]);

        $response->assertRedirect(route('admin.exams'));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('successmessage');

        $this->assertSame($before + 1, Exam::where('school_id', $this->school->id)->count());
        $this->assertDatabaseHas('exams', [
            'school_id' => $this->school->id,
            'teacher_id' => $this->peerTeacher->id,
            'subject_id' => $this->subject->id,
        ]);
    }

    public function test_peer_teacher_who_is_not_ct_cannot_create(): void
    {
        $response = $this->actingAs($this->peerTeacher)->post(route('teacher.exams.store'), [
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $this->examType->id,
            'teacher_id' => $this->peerTeacher->id,
        ]);

        $response->assertForbidden();
    }
}
