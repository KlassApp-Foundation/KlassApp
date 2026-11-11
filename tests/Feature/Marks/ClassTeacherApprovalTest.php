<?php

namespace Tests\Feature\Marks;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Class teachers review their own class: approve, and return with a reason.
 * A subject teacher who does not head the class cannot; nobody can act on
 * another class. Mirrors the release scoping already in place.
 */
class ClassTeacherApprovalTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private Section $section;

    private Section $otherSection;

    private Subject $subject;

    private Exam $exam;

    private User $classTeacher;

    private User $subjectTeacher;

    private User $otherTeacher;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('exam_types')->insert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0,
                'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Review School', 'slug' => 'review-school',
            'email' => 'review@test.test', 'phone' => '+256700000031',
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Term 1', 'starts_on' => '2026-01-01', 'ends_on' => '2026-04-30',
            'status' => 'current',
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1,
        ]);

        $this->section = Section::create(['school_id' => $this->school->id, 'name' => 'P.1', 'status' => 1]);
        $this->otherSection = Section::create(['school_id' => $this->school->id, 'name' => 'P.2', 'status' => 1]);

        $this->classTeacher = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 5,
            'email' => 'ct@review.test', 'name' => 'Class Teacher', 'status' => 'active',
        ]);
        $this->subjectTeacher = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 5,
            'email' => 'st@review.test', 'name' => 'Subject Teacher', 'status' => 'active',
        ]);
        $this->otherTeacher = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 5,
            'email' => 'ot@review.test', 'name' => 'Other Teacher', 'status' => 'active',
        ]);
        $this->admin = User::factory()->create([
            'school_id' => $this->school->id, 'usergroup_id' => 3,
            'email' => 'admin@review.test', 'name' => 'Admin', 'status' => 'active',
        ]);

        StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'class_teacher_id' => $this->classTeacher->id,
            'status' => 1,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'name' => 'Mathematics',
            'type' => 'core',
        ]);

        $this->exam = Exam::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $term->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->subjectTeacher->id,
            'exam_type_id' => 1,
            'status' => 'undone',
        ]);
    }

    private function submittedFor(Exam $exam, int $classId): ExamMarksSubmission
    {
        return ExamMarksSubmission::create([
            'school_id' => $exam->school_id,
            'exam_id' => $exam->id,
            'class_id' => $classId,
            'subject_id' => $exam->subject_id,
            'teacher_id' => $exam->teacher_id,
            'status' => 'submitted',
            'approval_status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    public function test_the_class_teacher_approves_marks_for_their_own_class(): void
    {
        $row = $this->submittedFor($this->exam, $this->section->id);

        $affected = app(MarksSubmissionService::class)->approve($this->classTeacher, $this->exam);

        $this->assertSame(1, $affected);
        $this->assertSame('approved', $row->fresh()->approval_status);
        $this->assertSame($this->classTeacher->id, $row->fresh()->approved_by);
    }

    public function test_a_subject_teacher_who_does_not_head_the_class_cannot_approve(): void
    {
        $this->submittedFor($this->exam, $this->section->id);

        $this->expectException(HttpException::class);
        app(MarksSubmissionService::class)->approve($this->subjectTeacher, $this->exam);
    }

    public function test_another_class_teacher_cannot_approve_this_class(): void
    {
        $this->submittedFor($this->exam, $this->section->id);

        $this->expectException(HttpException::class);
        app(MarksSubmissionService::class)->approve($this->otherTeacher, $this->exam);
    }

    public function test_the_class_teacher_cannot_approve_by_naming_another_class(): void
    {
        $this->submittedFor($this->exam, $this->section->id);

        $this->expectException(HttpException::class);
        app(MarksSubmissionService::class)->approve($this->classTeacher, $this->exam, $this->otherSection->id);
    }

    public function test_returning_requires_a_reason_and_stays_in_the_class(): void
    {
        $row = $this->submittedFor($this->exam, $this->section->id);

        $returned = app(MarksSubmissionService::class)->returnToTeacher(
            $this->classTeacher,
            $this->exam,
            $this->section->id,
            $this->subject->id,
            'Two marks look wrong on the P.1 sheet.',
        );

        $this->assertSame('reopened', $returned->status);
        $this->assertSame('rejected', $returned->approval_status);
        $this->assertSame('Two marks look wrong on the P.1 sheet.', $returned->rejection_reason);
        $this->assertSame($this->classTeacher->id, $row->fresh()->rejected_by);
    }

    public function test_the_class_teacher_cannot_return_marks_for_another_class(): void
    {
        $this->submittedFor($this->exam, $this->section->id);

        $this->expectException(HttpException::class);
        app(MarksSubmissionService::class)->returnToTeacher(
            $this->classTeacher,
            $this->exam,
            $this->otherSection->id,
            $this->subject->id,
            'Wrong class on purpose, at least ten characters.',
        );
    }

    public function test_the_admin_can_still_approve_and_return(): void
    {
        $row = $this->submittedFor($this->exam, $this->section->id);

        $this->assertSame(1, app(MarksSubmissionService::class)->approve($this->admin, $this->exam));
        $this->assertSame('approved', $row->fresh()->approval_status);
    }

    public function test_the_sidebar_offers_review_marks_to_a_class_teacher_only(): void
    {
        $this->actingAs($this->classTeacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('Review marks');

        $this->actingAs($this->subjectTeacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertDontSee('Review marks');
    }

    public function test_the_teacher_approvals_page_lists_only_their_class(): void
    {
        $this->submittedFor($this->exam, $this->section->id);

        $otherExam = Exam::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'academic_term_id' => $this->exam->academic_term_id,
            'standard_id' => $this->exam->standard_id,
            'section_id' => $this->otherSection->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->otherTeacher->id,
            'exam_type_id' => 1,
            'status' => 'undone',
        ]);
        $this->submittedFor($otherExam, $this->otherSection->id);

        $this->actingAs($this->classTeacher)
            ->get(route('teacher.marks.approvals'))
            ->assertOk()
            ->assertSee('P.1')
            ->assertDontSee('P.2');
    }
}
