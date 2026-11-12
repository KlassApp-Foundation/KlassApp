<?php

namespace App\Services\Marks;

use App\Exceptions\MarksLockedException;
use App\Helpers\GradingHelper;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\User;
use App\Services\Academics\BaseClassExamRoster;
use App\Services\GradingSystemService;
use Illuminate\Validation\ValidationException;

/**
 * The only numeric mark write. The teacher form, the single-mark edit, the
 * spreadsheet import, and Toshi (a subject teacher, a class teacher, or a
 * school admin) all call save(). Parents are not notified here, and the exam
 * status is left unchanged. Nursery ratings are not marks and do not come here.
 */
class ExamMarkWriter
{
    public function __construct(
        private GradingSystemService $grading,
        private BaseClassExamRoster $roster,
        private ReleasedMarksLock $releases,
    ) {}

    public function save(User $actor, Exam $exam, int $studentId, mixed $mark, ?string $correctionReason = null): Marks
    {
        if ((int) $actor->school_id !== (int) $exam->school_id) {
            abort(403, 'Submitted student is not authorized for this school.');
        }

        $student = User::query()
            ->where('id', $studentId)
            ->where('school_id', $exam->school_id)
            ->where('usergroup_id', 6)
            ->first();

        if ($student === null) {
            abort(403, 'Submitted student is not authorized for this school.');
        }

        $this->roster->assertStudentAllowed($actor, $exam, (int) $student->id);

        $this->assertWritable($exam);
        $this->assertCorrectionReason($exam, $correctionReason);

        $existing = Marks::query()
            ->where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('school_id', $exam->school_id)
            ->where('subject_id', $exam->subject_id)
            ->first();
        $old = $this->storedToken($existing);
        $absent = $this->isAbsent($mark);
        $new = $absent ? 'ABS' : $this->numericToken($mark);

        $row = Marks::updateOrCreate(
            [
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'school_id' => $exam->school_id,
                'subject_id' => $exam->subject_id,
            ],
            [
                'teacher_id' => (int) $actor->id,
                'marks' => $absent ? null : $mark,
                'grade' => $absent ? 'AB' : $this->letter($exam, $mark),
                'absent' => $absent,
                'section_id' => $exam->section_id,
            ]
        );

        if ($old !== $new) {
            $this->recordMark($actor, $exam, $row, $existing === null ? 'entered' : 'changed', $old, $new);
        }

        return $row;
    }

    /**
     * Submitted, approved, and locked marks stay closed. A return or a reopen
     * is what opens them again. exams.status is not the workflow once a
     * submission row exists; a legacy submitted exam with no open row is closed too.
     */
    public function writesAreRefused(Exam $exam): bool
    {
        if ($this->releases->isLocked($exam)) {
            return true;
        }

        $submission = $this->submission($exam);

        if ($submission) {
            if ($submission->isApproved() || $submission->isLocked() || $submission->status === 'submitted') {
                return true;
            }

            if ($submission->status === 'reopened' || $submission->status === 'draft') {
                return $submission->status === 'draft' && $exam->status === 'submitted';
            }
        }

        return $exam->status === 'submitted';
    }

    public function assertWritable(Exam $exam): void
    {
        if ($this->releases->isLocked($exam)) {
            throw new MarksLockedException(null, 'These marks are released. An admin or head teacher has to reopen them before they can change.');
        }

        if (! $this->writesAreRefused($exam)) {
            return;
        }

        $submission = $this->submission($exam);
        if ($submission && $submission->isLocked()) {
            $deadline = $submission->deadline
                ? $submission->deadline->format('j M Y H:i')
                : null;
            throw new MarksLockedException($deadline);
        }

        throw new MarksLockedException(null, 'These marks are submitted or approved. A school admin has to return or reopen them before they can change.');
    }

    private function submission(Exam $exam): ?ExamMarksSubmission
    {
        return ExamMarksSubmission::query()
            ->where('school_id', $exam->school_id)
            ->where('exam_id', $exam->id)
            ->where('class_id', $exam->section_id)
            ->where('subject_id', $exam->subject_id)
            ->first();
    }

    public function maximum(Exam $exam): float
    {
        return 100.0;
    }

    /**
     * Absent is a state, not a zero. The entry screen and older sheets say AB;
     * the marks workbook says ABS. Both mean the same row.
     */
    public function isAbsent(mixed $mark): bool
    {
        return in_array(strtoupper(trim((string) $mark)), ['AB', 'ABS'], true);
    }

    public function exceedsMaximum(Exam $exam, mixed $mark): bool
    {
        if ($mark === null || $this->isAbsent($mark) || trim((string) $mark) === '' || ! is_numeric($mark)) {
            return false;
        }

        return (float) $mark > $this->maximum($exam);
    }

    /**
     * The same closed-mark test the writer enforces, for readers: the profile's
     * mark list and the correction-reason check both ask this question.
     */
    public function requiresCorrection(Exam $exam): bool
    {
        return $this->writesAreRefused($exam);
    }

    private function letter(Exam $exam, mixed $mark): string
    {
        $exam->loadMissing('standard');
        $level = $exam->standard ? GradingHelper::levelTypeForStandard($exam->standard) : null;
        if (is_string($level) && ! GradingHelper::scaleUsesNumericMarks($level)) {
            return '-';
        }

        return $this->grading->grade((int) $mark, (int) $exam->school_id, $exam) ?? '-';
    }

    private function assertCorrectionReason(Exam $exam, ?string $reason): void
    {
        if (! $this->requiresCorrection($exam)) {
            return;
        }

        $reason = trim((string) $reason);

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages([
                'correction_reason' => 'These marks were already submitted. Give a reason of at least 10 characters for correcting them.',
            ]);
        }

        if (mb_strlen($reason) > 500) {
            throw ValidationException::withMessages([
                'correction_reason' => 'Keep the correction reason under 500 characters.',
            ]);
        }
    }

    private function storedToken(?Marks $row): ?string
    {
        if ($row === null) {
            return null;
        }

        if ($row->isAbsent()) {
            return 'ABS';
        }

        return $this->numericToken($row->marks);
    }

    private function numericToken(mixed $mark): ?string
    {
        if ($mark === null || trim((string) $mark) === '') {
            return null;
        }

        if (! is_numeric($mark)) {
            return trim((string) $mark);
        }

        $number = (float) $mark;

        if (abs($number - round($number)) < 0.00001) {
            return (string) (int) round($number);
        }

        return rtrim(rtrim(sprintf('%.4F', $number), '0'), '.');
    }

    private function recordMark(User $actor, Exam $exam, Marks $row, string $action, ?string $old, ?string $new): void
    {
        activity()
            ->performedOn($row)
            ->causedBy($actor)
            ->useLog('marks')
            ->withProperties([
                'action' => $action,
                'actor_id' => (int) $actor->id,
                'school_id' => (int) $exam->school_id,
                'exam_id' => (int) $exam->id,
                'student_id' => (int) $row->student_id,
                'subject_id' => (int) $exam->subject_id,
                'section_id' => (int) $exam->section_id,
                'academic_year_id' => $exam->academic_year_id !== null ? (int) $exam->academic_year_id : null,
                'academic_term_id' => $exam->academic_term_id !== null ? (int) $exam->academic_term_id : null,
                'exam_set_id' => $exam->exam_set_id !== null ? (int) $exam->exam_set_id : null,
                'old_value' => $old,
                'new_value' => $new,
                'at' => now()->utc()->toIso8601String(),
            ])
            ->log($action === 'entered' ? 'marks.entered' : 'marks.changed');
    }
}
