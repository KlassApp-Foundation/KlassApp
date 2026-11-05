<?php

namespace App\Services\Marks;

use App\Helpers\GradingHelper;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\Academics\NurseryAssessment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * A teacher submit writes one submission row. It does not change
 * exams.status and it does not message parents.
 */
final class MarksSubmissionService
{
    /**
     * Submit for review needs a saved numeric mark, or a nursery rating.
     * An empty grid is not something to submit.
     */
    public function hasWorkToSubmit(Exam $exam): bool
    {
        $exam->loadMissing('standard');
        $level = $exam->standard ? GradingHelper::levelTypeForStandard($exam->standard) : null;

        if ($level === 'nursery') {
            return NurseryAssessment::query()
                ->where('exam_id', $exam->id)
                ->where('academic_term_id', $exam->academic_term_id)
                ->exists();
        }

        return Marks::query()
            ->where('school_id', $exam->school_id)
            ->where('exam_id', $exam->id)
            ->where('subject_id', $exam->subject_id)
            ->whereNotNull('marks')
            ->exists();
    }

    public function submit(Exam $exam, User $teacher): ExamMarksSubmission
    {
        $existing = ExamMarksSubmission::query()
            ->where('exam_id', $exam->id)
            ->where('class_id', $exam->section_id)
            ->where('subject_id', $exam->subject_id)
            ->first();

        if ($existing?->isLocked()) {
            throw ValidationException::withMessages([
                'submission' => 'These marks are locked. Ask an admin to reopen them before submitting again.',
            ]);
        }

        return ExamMarksSubmission::query()->updateOrCreate(
            [
                'exam_id' => $exam->id,
                'class_id' => $exam->section_id,
                'subject_id' => $exam->subject_id,
            ],
            [
                'school_id' => $exam->school_id,
                'teacher_id' => $teacher->id,
                'status' => 'submitted',
                'approval_status' => 'pending',
                'submitted_at' => now(),
            ],
        );
    }

    public function pendingCount(int $schoolId): int
    {
        return ExamMarksSubmission::query()
            ->where('school_id', $schoolId)
            ->where('status', 'submitted')
            ->where(function ($query) {
                $query->where('approval_status', 'pending')
                    ->orWhereNull('approval_status');
            })
            ->count();
    }
}
