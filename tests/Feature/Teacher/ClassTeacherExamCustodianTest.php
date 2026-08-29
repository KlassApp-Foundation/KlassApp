<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ExamAuthorization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PR-A: additive class-teacher custodian access to exams/marks.
 * Subject-teacher teacher_id access must remain unchanged.
 */
class ClassTeacherExamCustodianTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $subjectTeacher;

    private User $classTeacher;

    private User $peerTeacher;

    private User $student;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Section $section;

    private Section $otherSection;

    private Standard $standard;

    private Subject $subject;

    private ExamType $examType;

    private Exam $subjectOwnedExam;

    private StandardLink $stream;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeTeacher::class);

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'CT Custodian School',
            'slug' => 'ct-custodian-' . uniqid(),
            'email' => 'ct-custodian-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->subjectTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Subject Teacher',
            'email' => 'subject.ct@t.sch.ug',
        ]);

        $this->classTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Class Teacher',
            'email' => 'class.ct@t.sch.ug',
        ]);

        $this->peerTeacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Peer Teacher',
            'email' => 'peer.ct@t.sch.ug',
        ]);

        $this->student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Custodian Student',
            'email' => 'student.ct@t.sch.ug',
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $this->student->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'firstname' => 'Custodian',
            'lastname' => 'Student',
            'status' => 'active',
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
            'name' => 'Term I',
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

        $this->examType = ExamType::create([
            'name' => 'Mid Term',
            'code' => 'MID',
            'contributes_to_report_total' => true,
        ]);

        SchoolGradingSystem::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'grade' => 'D1',
            'points' => 1,
            'min_score' => 85,
            'max_score' => 100,
            'remark' => 'Excellent',
        ]);

        SchoolGradingSystem::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'grade' => 'D2',
            'points' => 2,
            'min_score' => 80,
            'max_score' => 84,
            'remark' => 'V.Good',
        ]);

        SchoolGradingSystem::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'grade' => 'C3',
            'points' => 3,
            'min_score' => 75,
            'max_score' => 79,
            'remark' => 'Good',
        ]);

        SchoolGradingSystem::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'grade' => 'C4',
            'points' => 4,
            'min_score' => 70,
            'max_score' => 74,
            'remark' => 'Fair',
        ]);

        SchoolGradingSystem::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'grade' => 'P7',
            'points' => 7,
            'min_score' => 0,
            'max_score' => 69,
            'remark' => 'Pass',
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

        $this->subjectOwnedExam = Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->term->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->subjectTeacher->id,
            'exam_type_id' => $this->examType->id,
            'status' => 'undone',
        ]);
    }

    /** #1 Subject teacher on teacher_id — save still works (regression). */
    public function test_subject_teacher_can_still_save_marks_on_assigned_exam(): void
    {
        $response = $this->actingAs($this->subjectTeacher)
            ->post(route('teacher.exam.marks.save', $this->subjectOwnedExam), [
                'marks' => [
                    $this->student->id => 90,
                ],
            ]);

        $response->assertRedirect(route('teacher.exam.marks'));
        $this->assertDatabaseHas('marks', [
            'exam_id' => $this->subjectOwnedExam->id,
            'student_id' => $this->student->id,
            'marks' => 90,
            'teacher_id' => $this->subjectTeacher->id,
        ]);
    }

    /** #2 Peer (not assignee, not CT) — still 403 (regression). */
    public function test_peer_teacher_without_assignment_cannot_save_marks(): void
    {
        $response = $this->actingAs($this->peerTeacher)
            ->post(route('teacher.exam.marks.save', $this->subjectOwnedExam), [
                'marks' => [
                    $this->student->id => 55,
                ],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('marks', [
            'exam_id' => $this->subjectOwnedExam->id,
            'student_id' => $this->student->id,
            'marks' => 55,
        ]);
    }

    /** #3 Class teacher may save marks on exam assigned to another teacher. */
    public function test_class_teacher_can_save_marks_on_subject_teachers_exam(): void
    {
        $response = $this->actingAs($this->classTeacher)
            ->post(route('teacher.exam.marks.save', $this->subjectOwnedExam), [
                'marks' => [
                    $this->student->id => 88,
                ],
            ]);

        $response->assertRedirect(route('teacher.exam.marks'));
        $this->assertDatabaseHas('marks', [
            'exam_id' => $this->subjectOwnedExam->id,
            'student_id' => $this->student->id,
            'marks' => 88,
            // Stamp stays the assigned subject teacher (saveExamMarks behaviour)
            'teacher_id' => $this->subjectTeacher->id,
        ]);
    }

    /** #4 Class teacher of a different section — 403. */
    public function test_class_teacher_of_other_section_cannot_save_marks(): void
    {
        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->otherSection->id,
            'class_teacher_id' => $this->peerTeacher->id,
            'stream' => 'A',
            'status' => 1,
        ]);

        // peerTeacher is CT of otherSection only — not of subjectOwnedExam's section
        $response = $this->actingAs($this->peerTeacher)
            ->post(route('teacher.exam.marks.save', $this->subjectOwnedExam), [
                'marks' => [
                    $this->student->id => 40,
                ],
            ]);

        $response->assertForbidden();
    }

    /** #5 Cross-school class teacher — 403. */
    public function test_cross_school_class_teacher_cannot_act_on_exam(): void
    {
        $otherSchool = School::create([
            'name' => 'Other CT School',
            'slug' => 'other-ct-' . uniqid(),
            'email' => 'other-ct-' . uniqid() . '@t.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $foreignCt = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $otherSchool->id,
            'name' => 'Foreign CT',
            'email' => 'foreign.ct@t.sch.ug',
        ]);

        // Spoof: same numeric section id cannot help — school_id check fails
        $response = $this->actingAs($foreignCt)
            ->post(route('teacher.exam.marks.save', $this->subjectOwnedExam), [
                'marks' => [
                    $this->student->id => 10,
                ],
            ]);

        $response->assertForbidden();
    }

    /** #6 List: CT sees class exams they don't own; subject teacher only own. */
    public function test_exam_list_includes_class_exams_for_ct_and_only_owned_for_subject_teacher(): void
    {
        $auth = app(ExamAuthorization::class);
        $this->assertTrue($auth->canActOnExam($this->classTeacher, $this->subjectOwnedExam));
        $this->assertTrue($auth->canActOnExam($this->subjectTeacher, $this->subjectOwnedExam));
        $this->assertFalse($auth->canActOnExam($this->peerTeacher, $this->subjectOwnedExam));

        $ctList = $this->actingAs($this->classTeacher)
            ->get(route('teacher.exam.marks'));
        $ctList->assertOk();
        $ctList->assertSee('MATHEMATICS');
        $ctList->assertSee(route('teacher.exam.marks.enter', $this->subjectOwnedExam), false);

        $subjectList = $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.exam.marks'));
        $subjectList->assertOk();
        $subjectList->assertSee('MATHEMATICS');
        $subjectList->assertSee(route('teacher.exam.marks.enter', $this->subjectOwnedExam), false);

        $peerList = $this->actingAs($this->peerTeacher)
            ->get(route('teacher.exam.marks'));
        $peerList->assertOk();
        $peerList->assertDontSee(route('teacher.exam.marks.enter', $this->subjectOwnedExam), false);
    }

    public function test_class_teacher_can_view_and_enter_marks_pages_for_subject_exam(): void
    {
        Marks::create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'exam_id' => $this->subjectOwnedExam->id,
            'teacher_id' => $this->subjectTeacher->id,
            'school_id' => $this->school->id,
            'marks' => 77,
            'grade' => 'C3',
        ]);

        $this->actingAs($this->classTeacher)
            ->get(route('teacher.exam.marks.view', $this->subjectOwnedExam))
            ->assertOk()
            ->assertSee('77');

        $this->actingAs($this->classTeacher)
            ->get(route('teacher.exam.marks.enter', $this->subjectOwnedExam))
            ->assertOk();

        $this->actingAs($this->peerTeacher)
            ->get(route('teacher.exam.marks.view', $this->subjectOwnedExam))
            ->assertForbidden();

        $this->actingAs($this->peerTeacher)
            ->get(route('teacher.exam.marks.enter', $this->subjectOwnedExam))
            ->assertForbidden();
    }

    public function test_class_teacher_can_update_mark_and_subject_teacher_can_still_update_after(): void
    {
        Marks::create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->section->id,
            'exam_id' => $this->subjectOwnedExam->id,
            'teacher_id' => $this->subjectTeacher->id,
            'school_id' => $this->school->id,
            'marks' => 70,
            'grade' => 'C4',
        ]);

        $this->actingAs($this->classTeacher)
            ->patch(route('teacher.marks.update', [
                'exam' => $this->subjectOwnedExam,
                'student' => $this->student,
            ]), ['marks' => 82])
            ->assertRedirect(route('teacher.exam.marks'));

        $this->assertDatabaseHas('marks', [
            'exam_id' => $this->subjectOwnedExam->id,
            'student_id' => $this->student->id,
            'marks' => 82,
        ]);

        $this->actingAs($this->subjectTeacher)
            ->patch(route('teacher.marks.update', [
                'exam' => $this->subjectOwnedExam,
                'student' => $this->student,
            ]), ['marks' => 91])
            ->assertRedirect(route('teacher.exam.marks'));

        $this->assertDatabaseHas('marks', [
            'exam_id' => $this->subjectOwnedExam->id,
            'student_id' => $this->student->id,
            'marks' => 91,
        ]);
    }

    public function test_combined_marksheet_allows_class_teacher_via_shared_helper_path(): void
    {
        $this->actingAs($this->classTeacher)
            ->get(route('teacher.exam.combinedMarksheet', $this->stream))
            ->assertOk();

        $this->actingAs($this->peerTeacher)
            ->get(route('teacher.exam.combinedMarksheet', $this->stream))
            ->assertForbidden();
    }
}
