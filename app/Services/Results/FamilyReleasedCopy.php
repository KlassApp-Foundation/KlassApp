<?php

namespace App\Services\Results;

use App\Models\Academics\Exam;
use App\Models\MarksRelease;
use App\Models\Records\RecordSnapshot;
use App\Models\User;

/**
 * Marks a family was shown at release, kept while a reopen is in progress.
 *
 * Live marks can change after reopen. Until a newer release replaces this
 * one, the snapshot written for that release is what families see.
 */
final class FamilyReleasedCopy
{
    /** @var array<string, array<int, array{mark: float, grade: ?string}>|null> */
    private array $cache = [];

    /**
     * Subject marks from the saved copy, or null when the live mark is still
     * the released one (or no copy was saved).
     *
     * @return array<int, array{mark: float, grade: ?string}>|null
     */
    public function subjectsFor(User $student, int $schoolId, Exam $exam): ?array
    {
        if ($schoolId <= 0 || $exam->exam_type_id === null || $exam->academic_term_id === null) {
            return null;
        }

        $key = $schoolId.':'.(int) $student->id.':'.(int) $exam->exam_type_id.':'.(int) $exam->academic_term_id.':'.(int) $exam->section_id;
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $release = $this->latest($schoolId, $exam);
        if ($release === null || $release->status !== MarksRelease::STATUS_REOPENED) {
            return $this->cache[$key] = null;
        }

        $snapshot = RecordSnapshot::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->where('release_id', $release->id)
            ->orderByDesc('version')
            ->first();

        if ($snapshot === null) {
            return $this->cache[$key] = null;
        }

        $marks = [];
        foreach ((array) ($snapshot->payload['subjects'] ?? []) as $subject) {
            if (! is_array($subject) || ! isset($subject['subject_id']) || ! is_numeric($subject['mark'] ?? null)) {
                continue;
            }
            $marks[(int) $subject['subject_id']] = [
                'mark' => (float) $subject['mark'],
                'grade' => isset($subject['grade']) ? (string) $subject['grade'] : null,
            ];
        }

        return $this->cache[$key] = $marks === [] ? null : $marks;
    }

    private function latest(int $schoolId, Exam $exam): ?MarksRelease
    {
        return MarksRelease::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', [MarksRelease::STATUS_RELEASED, MarksRelease::STATUS_REOPENED])
            ->whereNotNull('released_at')
            ->whereHas('exam', function ($query) use ($schoolId, $exam): void {
                $query->where('school_id', $schoolId)
                    ->where('exam_type_id', $exam->exam_type_id)
                    ->where('academic_term_id', $exam->academic_term_id);
            })
            ->where(function ($query) use ($schoolId, $exam): void {
                $query->where('scope', 'school')
                    ->orWhere(function ($class) use ($schoolId, $exam): void {
                        $class->where('scope', 'class')
                            ->where(function ($match) use ($schoolId, $exam): void {
                                $match->whereHas('standardLink', fn ($link) => $link
                                    ->where('school_id', $schoolId)
                                    ->where('section_id', $exam->section_id))
                                    ->orWhereHas('exam', fn ($anchor) => $anchor
                                        ->where('school_id', $schoolId)
                                        ->where('section_id', $exam->section_id));
                            });
                    });
            })
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->first();
    }
}
