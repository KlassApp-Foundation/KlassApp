<?php

namespace App\Services\Marks;

use App\Helpers\GradingHelper;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\Academics\NurseryAssessment;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Notifications\MarksSubmissionNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * One state machine for a class, subject, and sitting.
 * Submit, approve, return, lock, and reopen all come through here.
 * exams.status is not this workflow. Parents are not messaged.
 */
final class MarksSubmissionService
{
    /** @var list<int> */
    private const SCHOOL_ADMIN_GROUPS = [1, 2, 3];

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
        $this->assertSameSchool($teacher, $exam);

        if (! $this->hasWorkToSubmit($exam)) {
            throw ValidationException::withMessages([
                'submission' => 'Save marks before submitting them for review.',
            ]);
        }

        $existing = $this->find($exam, (int) $exam->section_id, (int) $exam->subject_id);

        if ($existing?->isLocked() || $existing?->isApproved() || $existing?->status === 'submitted') {
            throw ValidationException::withMessages([
                'submission' => 'These marks are already in review.',
            ]);
        }

        $from = $existing?->status;
        $row = ExamMarksSubmission::query()->updateOrCreate(
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

        $this->log($teacher, $row, 'submitted', $from, 'submitted');
        $this->notifyAdmins($exam, $teacher, 'submitted', $exam->subject->name ?? 'a subject');

        return $row;
    }

    public function approve(User $actor, Exam $exam, ?int $classId = null, ?int $subjectId = null): int
    {
        $this->assertSchoolAdmin($actor, $exam);

        $query = ExamMarksSubmission::query()
            ->where('school_id', $exam->school_id)
            ->where('exam_id', $exam->id);

        if ($classId !== null && $subjectId !== null) {
            $this->assertClassAndSubject($exam, $classId, $subjectId);
            $query->where('class_id', $classId)->where('subject_id', $subjectId);
        } elseif ($subjectId !== null) {
            $this->assertClassAndSubject($exam, (int) $exam->section_id, $subjectId);
            $query->where('subject_id', $subjectId);
        }

        $targets = $query->get();
        if ($targets->isEmpty()) {
            throw ValidationException::withMessages([
                'approval' => 'No matching submissions found to approve.',
            ]);
        }

        $blocked = $targets->reject(fn (ExamMarksSubmission $row) => $row->isApprovable() || $row->isApproved());
        if ($blocked->isNotEmpty()) {
            throw ValidationException::withMessages([
                'approval' => 'Only a submitted or locked subject can be approved.',
            ]);
        }

        $affected = 0;
        foreach ($targets as $row) {
            if ($row->isApproved()) {
                continue;
            }
            $from = $row->status;
            $row->approval_status = 'approved';
            $row->approved_at = now();
            $row->approved_by = $actor->id;
            $row->save();
            $this->log($actor, $row, 'approved', $from, $row->status);
            $this->notifyTeacher($row, 'approved', 'Your marks were approved.');
            $affected++;
        }

        return $affected;
    }

    public function returnToTeacher(User $actor, Exam $exam, int $classId, int $subjectId, string $reason): ExamMarksSubmission
    {
        $this->assertSchoolAdmin($actor, $exam);
        $this->assertClassAndSubject($exam, $classId, $subjectId);
        $reason = $this->reason($reason, 'return_reason');

        $row = $this->find($exam, $classId, $subjectId);
        if ($row === null) {
            throw ValidationException::withMessages([
                'submission' => 'No submission record found for this exam, class, and subject.',
            ]);
        }

        if (! in_array($row->status, ['submitted', 'locked', 'reopened'], true) && ! $row->isApproved()) {
            throw ValidationException::withMessages([
                'submission' => 'Return a subject that has been submitted, locked, or approved.',
            ]);
        }

        if ($row->status === 'reopened' && ! $row->isApproved() && ! $row->isReturned()) {
            throw ValidationException::withMessages([
                'submission' => 'This subject is already open for editing.',
            ]);
        }

        $from = $row->status;
        $row->status = 'reopened';
        $row->approval_status = 'rejected';
        $row->rejection_reason = $reason;
        $row->rejected_at = now();
        $row->rejected_by = $actor->id;
        $row->reopen_reason = $reason;
        $row->reopened_at = now();
        $row->reopened_by = $actor->id;
        $row->save();

        $this->log($actor, $row, 'returned', $from, 'reopened', $reason);
        $this->notifyTeacher($row, 'returned', 'Your marks were returned. Reason: '.$reason);

        return $row->fresh();
    }

    public function lock(User $actor, Exam $exam, int $classId, int $subjectId): ExamMarksSubmission
    {
        $this->assertSchoolAdmin($actor, $exam);
        $this->assertClassAndSubject($exam, $classId, $subjectId);

        $row = $this->find($exam, $classId, $subjectId);
        if ($row === null) {
            throw ValidationException::withMessages([
                'submission' => 'No submission record found for this exam, class, and subject.',
            ]);
        }

        if ($row->isLocked()) {
            throw ValidationException::withMessages([
                'submission' => 'This submission is already locked.',
            ]);
        }

        if ($row->isApproved()) {
            throw ValidationException::withMessages([
                'submission' => 'An approved subject is not locked again. Return it if the marks need to change.',
            ]);
        }

        if (! in_array($row->status, ['submitted', 'reopened', 'draft'], true)) {
            throw ValidationException::withMessages([
                'submission' => 'Only an open or submitted subject can be locked.',
            ]);
        }

        return $this->applyLock($row, $actor, 'locked');
    }

    public function reopen(User $actor, Exam $exam, int $classId, int $subjectId, string $reason): ExamMarksSubmission
    {
        $this->assertSchoolAdmin($actor, $exam);
        $this->assertClassAndSubject($exam, $classId, $subjectId);
        $reason = $this->reason($reason, 'reopen_reason');

        $row = $this->find($exam, $classId, $subjectId);
        if ($row === null) {
            throw ValidationException::withMessages([
                'submission' => 'No submission record found for this exam, class, and subject.',
            ]);
        }

        if (! $row->isLocked() && ! $row->isApproved()) {
            throw ValidationException::withMessages([
                'submission' => 'Only a locked or approved subject can be reopened.',
            ]);
        }

        $from = $row->status;
        $row->status = 'reopened';
        $row->approval_status = 'pending';
        $row->reopened_at = now();
        $row->reopened_by = $actor->id;
        $row->reopen_reason = $reason;
        $row->save();

        $this->log($actor, $row, 'reopened', $from, 'reopened', $reason);
        $this->notifyTeacher($row, 'reopened', 'Your marks were reopened. Reason: '.$reason);

        return $row->fresh();
    }

    /**
     * Deadline job. There is no signed-in admin; the log records the command.
     */
    public function lockExpired(ExamMarksSubmission $submission): ExamMarksSubmission
    {
        if ($submission->isLocked() || $submission->isApproved()) {
            return $submission;
        }

        return $this->applyLock($submission, null, 'locked');
    }

    public function lockPlaceholder(Exam $exam, int $classId, int $subjectId): ExamMarksSubmission
    {
        $row = ExamMarksSubmission::query()->create([
            'school_id' => $exam->school_id,
            'exam_id' => $exam->id,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'teacher_id' => $exam->teacher_id,
            'status' => 'locked',
            'approval_status' => 'pending',
            'deadline' => $exam->default_deadline,
            'locked_at' => now(),
        ]);

        $this->log(null, $row, 'locked', null, 'locked');

        return $row;
    }

    public function pendingCount(int $schoolId): int
    {
        return ExamMarksSubmission::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', ['submitted', 'locked'])
            ->where(function ($query) {
                $query->where('approval_status', 'pending')
                    ->orWhereNull('approval_status');
            })
            ->count();
    }

    public function awaitingReview(int $schoolId)
    {
        return ExamMarksSubmission::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', ['submitted', 'locked'])
            ->where(function ($query) {
                $query->where('approval_status', 'pending')
                    ->orWhereNull('approval_status');
            })
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');
    }

    private function applyLock(ExamMarksSubmission $row, ?User $actor, string $action): ExamMarksSubmission
    {
        $from = $row->status;
        $row->status = 'locked';
        $row->locked_at = now();
        if ($row->approval_status !== 'approved') {
            $row->approval_status = 'pending';
        }
        $row->save();
        $this->log($actor, $row, $action, $from, 'locked');
        $this->notifyTeacher($row, 'locked', 'Your marks were locked.');

        return $row->fresh();
    }

    private function find(Exam $exam, int $classId, int $subjectId): ?ExamMarksSubmission
    {
        return ExamMarksSubmission::query()
            ->where('school_id', $exam->school_id)
            ->where('exam_id', $exam->id)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->first();
    }

    private function assertSameSchool(User $actor, Exam $exam): void
    {
        if ((int) $actor->school_id !== (int) $exam->school_id) {
            abort(403, 'This exam is not in your school.');
        }
    }

    private function assertSchoolAdmin(User $actor, Exam $exam): void
    {
        $this->assertSameSchool($actor, $exam);

        if (! in_array((int) $actor->usergroup_id, self::SCHOOL_ADMIN_GROUPS, true)) {
            abort(403, 'Only a school admin can review marks.');
        }
    }

    private function assertClassAndSubject(Exam $exam, int $classId, int $subjectId): void
    {
        $sectionOk = Section::query()->whereKey($classId)->where('school_id', $exam->school_id)->exists();
        $subjectOk = Subject::query()->whereKey($subjectId)->where('school_id', $exam->school_id)->exists();

        if (! $sectionOk || ! $subjectOk) {
            throw ValidationException::withMessages([
                'submission' => 'That class or subject is not in this school.',
            ]);
        }
    }

    private function reason(string $reason, string $field): string
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages([
                $field => 'Give a reason of at least 10 characters.',
            ]);
        }

        if (mb_strlen($reason) > 500) {
            throw ValidationException::withMessages([
                $field => 'Keep the reason under 500 characters.',
            ]);
        }

        return $reason;
    }

    private function log(?User $actor, ExamMarksSubmission $row, string $action, ?string $from, string $to, ?string $reason = null): void
    {
        $logger = activity()
            ->performedOn($row)
            ->withProperties([
                'school_id' => (int) $row->school_id,
                'exam_id' => (int) $row->exam_id,
                'class_id' => (int) $row->class_id,
                'subject_id' => (int) $row->subject_id,
                'actor_id' => $actor ? (int) $actor->id : null,
                'from_status' => $from,
                'to_status' => $to,
                'action' => $action,
                'reason' => $reason,
                'at' => now()->utc()->toIso8601String(),
            ])
            ->useLog('marks');

        if ($actor) {
            $logger->causedBy($actor);
        }

        $logger->log('marks.submission.'.$action);
    }

    private function notifyAdmins(Exam $exam, User $teacher, string $kind, string $subjectName): void
    {
        $admins = User::query()
            ->where('school_id', $exam->school_id)
            ->whereIn('usergroup_id', self::SCHOOL_ADMIN_GROUPS)
            ->where('status', 'active')
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        $this->send($admins, $kind, (int) $exam->id, $teacher->name.' submitted '.$subjectName.' for review.');
    }

    private function notifyTeacher(ExamMarksSubmission $row, string $kind, string $message): void
    {
        $teacher = User::query()
            ->where('id', $row->teacher_id)
            ->where('school_id', $row->school_id)
            ->where('status', 'active')
            ->first();

        if ($teacher === null) {
            return;
        }

        $this->send(collect([$teacher]), $kind, (int) $row->exam_id, $message);
    }

    private function send($users, string $kind, int $examId, string $message): void
    {
        try {
            Notification::send($users, new MarksSubmissionNotification($message, $examId, $kind));
        } catch (\Throwable $e) {
            Log::warning('Marks submission notice failed: '.$e->getMessage());
        }
    }
}
