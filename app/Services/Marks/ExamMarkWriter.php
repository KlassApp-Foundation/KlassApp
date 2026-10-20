<?php

namespace App\Services\Marks;

use App\Exceptions\MarksLockedException;
use App\Helpers\GradingHelper;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\User;
use App\Services\GradingSystemService;
use Illuminate\Validation\ValidationException;

/**
 * One numeric mark write for the teacher form and for Toshi.
 * Parents are not notified here, and the exam status is left unchanged.
 */
class ExamMarkWriter
{
    public function __construct(private GradingSystemService $grading) {}

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

        $this->assertNotLocked($exam);
        $this->assertCorrectionReason($exam, $correctionReason);

        return Marks::updateOrCreate(
            [
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'school_id' => $exam->school_id,
                'subject_id' => $exam->subject_id,
            ],
            [
                'teacher_id' => $exam->teacher_id ?: $actor->id,
                'marks' => $mark,
                'grade' => $this->letter($exam, $mark),
                'section_id' => $exam->section_id,
            ]
        );
    }

    private function assertNotLocked(Exam $exam): void
    {
        $submission = ExamMarksSubmission::query()
            ->where('exam_id', $exam->id)
            ->where('class_id', $exam->section_id)
            ->where('subject_id', $exam->subject_id)
            ->first();

        if ($submission && $submission->isLocked()) {
            $deadline = $submission->deadline
                ? $submission->deadline->format('j M Y H:i')
                : null;
            throw new MarksLockedException($deadline);
        }
    }

    public function requiresCorrection(Exam $exam): bool
    {
        if ($exam->status === 'submitted') {
            return true;
        }

        return ExamMarksSubmission::query()
            ->where('school_id', $exam->school_id)
            ->where('exam_id', $exam->id)
            ->where('class_id', $exam->section_id)
            ->where('subject_id', $exam->subject_id)
            ->where('status', 'submitted')
            ->exists();
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
}
