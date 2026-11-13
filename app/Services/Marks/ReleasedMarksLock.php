<?php

namespace App\Services\Marks;

use App\Models\Academics\Exam;
use App\Models\MarksRelease;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * A released sitting cannot be edited until an admin or head teacher reopens it.
 *
 * School scope locks every class of that exam type and term. Class scope locks
 * the released class, every subject in it, and no other class. Another school
 * is never in scope.
 */
class ReleasedMarksLock
{
    /** @var list<int> */
    private const REOPEN_GROUPS = [1, 3, 4];

    public function isLocked(Exam $exam): bool
    {
        return $this->lockingRelease($exam) !== null;
    }

    public function lockingRelease(Exam $exam): ?MarksRelease
    {
        if ($exam->exam_type_id === null || $exam->academic_term_id === null) {
            return null;
        }

        return MarksRelease::query()
            ->select('marks_releases.*')
            ->join('exams as release_exams', 'release_exams.id', '=', 'marks_releases.exam_id')
            ->where('marks_releases.school_id', $exam->school_id)
            ->where('marks_releases.status', MarksRelease::STATUS_RELEASED)
            ->where('release_exams.school_id', $exam->school_id)
            ->where('release_exams.exam_type_id', $exam->exam_type_id)
            ->where('release_exams.academic_term_id', $exam->academic_term_id)
            ->whereNull('release_exams.deleted_at')
            ->where(function ($query) use ($exam): void {
                $query->where('marks_releases.scope', 'school')
                    ->orWhere('release_exams.section_id', $exam->section_id);
            })
            ->orderByDesc('marks_releases.id')
            ->first();
    }

    public function reopen(User $actor, MarksRelease $release, string $reason): MarksRelease
    {
        if ((int) $actor->school_id <= 0 || (int) $actor->school_id !== (int) $release->school_id) {
            abort(403, 'You can reopen released marks only for your own school.');
        }

        if (! in_array((int) $actor->usergroup_id, self::REOPEN_GROUPS, true)) {
            abort(403, 'Only an admin or a head teacher can reopen released marks.');
        }

        if ($release->status !== MarksRelease::STATUS_RELEASED) {
            throw ValidationException::withMessages([
                'reason' => 'Only a released set can be reopened.',
            ]);
        }

        $reason = trim($reason);

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages([
                'reason' => 'Give a reason of at least 10 characters.',
            ]);
        }

        if (mb_strlen($reason) > 500) {
            throw ValidationException::withMessages([
                'reason' => 'Keep the reason under 500 characters.',
            ]);
        }

        $release->forceFill(['status' => MarksRelease::STATUS_REOPENED])->save();

        activity()
            ->performedOn($release)
            ->causedBy($actor)
            ->useLog('marks')
            ->withProperties([
                'action' => 'reopened',
                'old_value' => MarksRelease::STATUS_RELEASED,
                'new_value' => MarksRelease::STATUS_REOPENED,
                'reason' => $reason,
                'exam_id' => (int) $release->exam_id,
                'school_id' => (int) $release->school_id,
                'actor_id' => (int) $actor->id,
                'at' => now()->utc()->toIso8601String(),
            ])
            ->log('marks.release.reopened');

        return $release;
    }
}
