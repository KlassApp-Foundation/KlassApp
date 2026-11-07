<?php

namespace Tests\Feature\StudentProfile;

use App\Exceptions\MarksLockedException;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\MarksRelease;
use App\Services\StudentProfile\StudentAttendanceRecorder;
use App\Services\StudentProfile\StudentMarksDraftService;
use App\Services\StudentProfile\StudentPaymentRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\StudentProfileFixture;
use Tests\TestCase;

/** The three actions on the admin student profile: record a payment, add marks (draft), record attendance. */
class StudentProfileActionsTest extends TestCase
{
    use RefreshDatabase;
    use StudentProfileFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStudentProfileFixture();
    }

    // ── payment against the term total ───────────────────────────────────────

    public function test_a_payment_is_recorded_as_a_general_payment_against_the_student(): void
    {
        $payment = app(StudentPaymentRecorder::class)->record($this->admin, $this->student, [
            'amount' => '300,000', 'payment_method' => 'mobile_money', 'paid_on' => now()->toDateString(), 'reference' => 'MM-7781',
        ]);

        $this->assertDatabaseHas('fee_payments', [
            'id' => $payment->id, 'school_id' => $this->school->id, 'user_id' => $this->student->id,
            'fee_category_id' => null, 'amount' => 300000, 'payment_method' => 'mobile_money', 'reference' => 'MM-7781',
            'recorded_by' => $this->admin->id, 'status' => 'paid',
        ]);
        $this->assertSame(1, ActivityLog::where('description', 'fees.payment_recorded')->where('causer_id', $this->admin->id)->count());
    }

    public function test_the_same_form_submitted_twice_records_one_payment(): void
    {
        $recorder = app(StudentPaymentRecorder::class);
        $input = ['amount' => '50000', 'payment_method' => 'cash', 'paid_on' => now()->toDateString()];

        $first = $recorder->record($this->admin, $this->student, $input, 'form-1');
        $second = $recorder->record($this->admin, $this->student, $input, 'form-1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, FeePayment::where('user_id', $this->student->id)->count());
    }

    public function test_payment_input_is_validated(): void
    {
        $recorder = app(StudentPaymentRecorder::class);
        $ok = ['amount' => '1000', 'payment_method' => 'cash', 'paid_on' => now()->toDateString()];

        foreach ([
            'zero' => ['amount' => '0'], 'negative' => ['amount' => '-5'], 'text' => ['amount' => 'lots'], 'huge' => ['amount' => '99999999999'],
            'bad method' => ['payment_method' => 'barter'], 'future' => ['paid_on' => now()->addDay()->toDateString()], 'no date' => ['paid_on' => ''],
        ] as $case => $override) {
            try {
                $recorder->record($this->admin, $this->student, $override + $ok);
                $this->fail("{$case} must be refused");
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame(0, FeePayment::count());
    }

    public function test_only_an_admin_of_the_students_school_may_record_a_payment(): void
    {
        $recorder = app(StudentPaymentRecorder::class);
        $input = ['amount' => '1000', 'payment_method' => 'cash', 'paid_on' => now()->toDateString()];

        foreach ([[$this->otherAdmin, $this->student], [$this->teacher, $this->student], [$this->admin, $this->otherStudent]] as [$actor, $student]) {
            try {
                $recorder->record($actor, $student, $input);
                $this->fail('expected 403');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertSame(0, FeePayment::count());
    }

    // ── marks, saved as a draft ──────────────────────────────────────────────

    public function test_marks_are_saved_as_drafts_that_never_touch_submission_approval_or_release(): void
    {
        $drafts = app(StudentMarksDraftService::class);
        [$sitting] = $drafts->sittings($this->student);

        $result = $drafts->save($this->admin, $this->student, $sitting['exam_type_id'], $sitting['term_id'], [
            $this->exams['English']->id => '88', $this->exams['Mathematics']->id => '93.5',
        ]);

        $this->assertSame(2, $result['saved']);
        $this->assertDatabaseHas('marks', ['student_id' => $this->student->id, 'exam_id' => $this->exams['English']->id, 'marks' => 88, 'grade' => 'A', 'section_id' => $this->section->id, 'teacher_id' => $this->teacher->id]);
        $this->assertDatabaseHas('marks', ['student_id' => $this->student->id, 'exam_id' => $this->exams['Mathematics']->id, 'marks' => 93.5]);

        $this->assertSame(0, ExamMarksSubmission::count(), 'no submission row is created: Submit stays the teacher\'s step');
        $this->assertSame(0, MarksRelease::count(), 'nothing is released');
        $this->assertSame('undone', $this->exams['English']->fresh()->status, 'the exam status is left alone');
        $this->assertSame(1, ActivityLog::where('description', 'marks.draft_added')->count());
    }

    public function test_a_subject_already_submitted_or_locked_is_reported_not_changed(): void
    {
        $this->draftMark('English', 70);
        $this->submission('English', 'submitted', 'pending');
        $this->submission('Mathematics', 'locked', 'pending');
        $drafts = app(StudentMarksDraftService::class);
        [$sitting] = $drafts->sittings($this->student);

        $rows = collect($drafts->subjects($this->student, $sitting['exam_type_id'], $sitting['term_id']))->keyBy('subject');
        $this->assertFalse($rows['English']['editable']);
        $this->assertFalse($rows['Mathematics']['editable']);

        $result = $drafts->save($this->admin, $this->student, $sitting['exam_type_id'], $sitting['term_id'], [
            $this->exams['English']->id => '99', $this->exams['Mathematics']->id => '99',
        ]);

        $this->assertSame(0, $result['saved']);
        $this->assertCount(2, $result['skipped']);
        $this->assertEquals(70, Marks::where('exam_id', $this->exams['English']->id)->value('marks'));
        $this->assertSame(0, Marks::where('exam_id', $this->exams['Mathematics']->id)->count());
    }

    public function test_marks_input_is_validated(): void
    {
        $drafts = app(StudentMarksDraftService::class);
        [$sitting] = $drafts->sittings($this->student);
        $english = $this->exams['English']->id;

        foreach (['over' => '101', 'negative' => '-1', 'text' => 'abc'] as $case => $value) {
            try {
                $drafts->save($this->admin, $this->student, $sitting['exam_type_id'], $sitting['term_id'], [$english => $value]);
                $this->fail("{$case} must be refused");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('marks.'.$english, $e->errors(), $case);
            }
        }

        try {
            $drafts->save($this->admin, $this->student, $sitting['exam_type_id'], $sitting['term_id'], [$english => '']);
            $this->fail('nothing entered must be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('marks', $e->errors());
        }
        $this->assertSame(0, Marks::count());
    }

    public function test_marks_cannot_be_added_to_another_classs_exam_or_by_another_school(): void
    {
        $drafts = app(StudentMarksDraftService::class);
        [$sitting] = $drafts->sittings($this->student);

        try {
            $drafts->save($this->admin, $this->student, $sitting['exam_type_id'], $sitting['term_id'], [999999 => '50']);
            $this->fail('an exam outside the class must be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('marks', $e->errors());
        }

        foreach ([[$this->otherAdmin, $this->student], [$this->teacher, $this->student]] as [$actor, $student]) {
            try {
                $drafts->save($actor, $student, $sitting['exam_type_id'], $sitting['term_id'], [$this->exams['English']->id => '50']);
                $this->fail('expected 403');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertSame(0, Marks::count());
    }

    public function test_a_locked_marks_row_still_refuses_through_the_shared_writer(): void
    {
        $this->submission('English', 'locked');
        $writer = app(\App\Services\Marks\ExamMarkWriter::class);

        $this->expectException(MarksLockedException::class);
        $writer->save($this->admin, $this->exams['English'], $this->student->id, 50);
    }

    // ── attendance for a day, with a comment ─────────────────────────────────

    public function test_attendance_is_recorded_for_a_day_with_a_comment_and_re_recording_updates_it(): void
    {
        $recorder = app(StudentAttendanceRecorder::class);
        $day = now()->subDay()->toDateString();

        $row = $recorder->record($this->admin, $this->student, ['date' => $day, 'status' => 'absent', 'comment' => 'Parent called: fever.']);

        $this->assertDatabaseHas('attendances', [
            'id' => $row->id, 'school_id' => $this->school->id, 'user_id' => $this->student->id, 'standardLink_id' => $this->link->id,
            'academic_year_id' => $this->year->id, 'status' => 0, 'remarks' => 'Parent called: fever.', 'session' => 'forenoon', 'recorded_by' => $this->admin->id,
        ]);

        $again = $recorder->record($this->admin, $this->student, ['date' => $day, 'status' => 'present', 'comment' => '']);

        $this->assertSame($row->id, $again->id);
        $this->assertSame(1, Attendance::where('user_id', $this->student->id)->count(), 'one row per student and day');
        $this->assertSame(1, (int) $again->fresh()->status);
        $this->assertNull($again->fresh()->remarks);
    }

    public function test_attendance_input_is_validated_and_scoped(): void
    {
        $recorder = app(StudentAttendanceRecorder::class);
        $ok = ['date' => now()->toDateString(), 'status' => 'absent'];

        foreach ([
            'future' => ['date' => now()->addDay()->toDateString()], 'bad status' => ['status' => 'late'],
            'long comment' => ['comment' => str_repeat('x', 501)], 'no date' => ['date' => ''],
        ] as $case => $override) {
            try {
                $recorder->record($this->admin, $this->student, $override + $ok);
                $this->fail("{$case} must be refused");
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        foreach ([[$this->otherAdmin, $this->student], [$this->teacher, $this->student]] as [$actor, $student]) {
            try {
                $recorder->record($actor, $student, $ok);
                $this->fail('expected 403');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertSame(0, Attendance::count());
    }

    public function test_a_student_without_a_class_cannot_get_attendance_or_marks(): void
    {
        \App\Models\StudentAcademic::where('user_id', $this->student->id)->delete();

        try {
            app(StudentAttendanceRecorder::class)->record($this->admin, $this->student, ['date' => now()->toDateString(), 'status' => 'absent']);
            $this->fail('expected a validation error');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('date', $e->errors());
        }
        $this->assertSame([], app(StudentMarksDraftService::class)->sittings($this->student));
    }
}
