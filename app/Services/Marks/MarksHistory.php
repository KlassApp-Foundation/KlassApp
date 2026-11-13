<?php

namespace App\Services\Marks;

use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\ActivityLog;
use App\Models\MarksRelease;
use App\Models\Subject;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Marks for one school, in the order a teacher reads a workbook:
 * academic year, term, exam set, class, subject.
 */
class MarksHistory
{
    /**
     * @return Collection<int, Marks>
     */
    public function forSchool(int $schoolId): Collection
    {
        return Marks::query()
            ->select('marks.*')
            ->join('exams', function ($join) use ($schoolId): void {
                $join->on('exams.id', '=', 'marks.exam_id')
                    ->where('exams.school_id', $schoolId)
                    ->whereNull('exams.deleted_at');
            })
            ->join('academic_years', function ($join) use ($schoolId): void {
                $join->on('academic_years.id', '=', 'exams.academic_year_id')
                    ->where('academic_years.school_id', $schoolId)
                    ->whereNull('academic_years.deleted_at');
            })
            ->join('academic_terms', function ($join) use ($schoolId): void {
                $join->on('academic_terms.id', '=', 'exams.academic_term_id')
                    ->where('academic_terms.school_id', $schoolId);
            })
            ->join('sections', function ($join) use ($schoolId): void {
                $join->on('sections.id', '=', 'exams.section_id')
                    ->where('sections.school_id', $schoolId)
                    ->whereNull('sections.deleted_at');
            })
            ->join('subjects', function ($join) use ($schoolId): void {
                $join->on('subjects.id', '=', 'marks.subject_id')
                    ->where('subjects.school_id', $schoolId)
                    ->whereNull('subjects.deleted_at');
            })
            ->where('marks.school_id', $schoolId)
            ->orderBy('academic_years.start_date')
            ->orderBy('academic_years.id')
            ->orderBy('academic_terms.starts_on')
            ->orderBy('academic_terms.id')
            ->orderByRaw('case when exams.exam_set_id is null then 1 else 0 end')
            ->orderBy('exams.exam_set_id')
            ->orderBy('sections.name')
            ->orderBy('sections.id')
            ->orderBy('subjects.name')
            ->orderBy('subjects.id')
            ->orderBy('marks.id')
            ->get();
    }

    /**
     * What happened to one class sitting, newest first.
     *
     * Released rows come from the release itself. The other rows come from
     * the marks activity log. Another school's rows are not included.
     *
     * @return list<array{
     *     kind: string,
     *     title: string,
     *     at: CarbonInterface,
     *     who: string,
     *     role: ?string,
     *     detail: ?string,
     *     note: bool,
     *     subject_id: ?int,
     *     student_id: ?int,
     *     old: ?string,
     *     new: ?string
     * }>
     */
    public function events(int $schoolId, int $sectionId, int $termId, int $examTypeId): array
    {
        $examIds = $this->examIds($schoolId, $sectionId, $termId, $examTypeId);
        $releases = $this->coveringReleases($schoolId, $sectionId, $termId, $examTypeId);
        $releaseExamIds = $releases->pluck('exam_id')->map(fn ($id) => (int) $id)->all();

        $logs = ActivityLog::query()
            ->where('log_name', 'marks')
            ->where('school_id', $schoolId)
            ->whereIn('description', [
                'marks.entered',
                'marks.changed',
                'marks.submission.submitted',
                'marks.submission.approved',
                'marks.submission.returned',
                'marks.release.reopened',
            ])
            ->orderByDesc('id')
            ->limit(400)
            ->get();

        $actorIds = $logs->pluck('causer_id')
            ->merge($releases->pluck('created_by'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $actors = User::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $actorIds)
            ->get()
            ->keyBy('id');

        $subjectIds = $logs->map(fn (ActivityLog $log) => (int) ($log->properties['subject_id'] ?? 0))
            ->filter()
            ->unique()
            ->values();
        $subjects = Subject::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $subjectIds)
            ->pluck('name', 'id');

        $events = [];
        foreach ($releases as $release) {
            if ($release->released_at === null) {
                continue;
            }
            $actor = $actors->get((int) $release->created_by);
            $events[] = $this->event(
                'released',
                'Released',
                $release->released_at,
                $actor,
                null,
                false,
                null,
                null,
                null,
                null,
            );
        }

        foreach ($logs as $log) {
            $props = is_array($log->properties) ? $log->properties : [];
            if ((int) ($props['school_id'] ?? $schoolId) !== $schoolId) {
                continue;
            }
            $examId = (int) ($props['exam_id'] ?? 0);
            $isSitting = in_array($examId, $examIds, true);
            $isReopen = $log->description === 'marks.release.reopened' && in_array($examId, $releaseExamIds, true);
            if (! $isSitting && ! $isReopen) {
                continue;
            }

            $subjectId = (int) ($props['subject_id'] ?? 0);
            $subjectId = $subjectId > 0 ? $subjectId : null;
            $subject = $subjectId !== null ? (string) ($subjects[$subjectId] ?? '') : '';
            $suffix = $subject !== '' ? ' · '.$subject : '';
            $actor = $actors->get((int) $log->causer_id);
            $studentId = (int) ($props['student_id'] ?? 0);
            $old = $this->token($props['old_value'] ?? null);
            $new = $this->token($props['new_value'] ?? null);
            $reason = trim((string) ($props['reason'] ?? ''));
            $at = $log->created_at instanceof CarbonInterface ? $log->created_at : Carbon::parse((string) $log->created_at);

            if ($log->description === 'marks.entered') {
                $events[] = $this->event('entered', 'Entered'.$suffix, $at, $actor, null, false, $subjectId, $studentId > 0 ? $studentId : null, $old, $new);

                continue;
            }
            if ($log->description === 'marks.changed') {
                $events[] = $this->event('changed', 'Changed'.$suffix, $at, $actor, null, false, $subjectId, $studentId > 0 ? $studentId : null, $old, $new);

                continue;
            }
            if ($log->description === 'marks.submission.approved') {
                $events[] = $this->event('approved', 'Approved'.$suffix, $at, $actor, null, false, $subjectId, null, null, null);

                continue;
            }
            if ($log->description === 'marks.submission.returned') {
                $events[] = $this->event('returned', 'Sent back'.$suffix, $at, $actor, $reason !== '' ? $reason : null, $reason !== '', $subjectId, null, null, null);

                continue;
            }
            if ($log->description === 'marks.release.reopened') {
                $events[] = $this->event('reopened', 'Reopened', $at, $actor, $reason !== '' ? $reason : null, $reason !== '', null, null, null, null);

                continue;
            }
            if ($log->description === 'marks.submission.submitted') {
                $from = (string) ($props['old_value'] ?? $props['from_status'] ?? '');
                $resubmitted = $from === 'reopened';
                $events[] = $this->event(
                    $resubmitted ? 'resubmitted' : 'submitted',
                    ($resubmitted ? 'Resubmitted' : 'Submitted').$suffix,
                    $at,
                    $actor,
                    null,
                    false,
                    $subjectId,
                    null,
                    null,
                    null,
                );
            }
        }

        usort($events, function (array $left, array $right): int {
            $byTime = $right['at']->getTimestamp() <=> $left['at']->getTimestamp();

            return $byTime !== 0 ? $byTime : strcmp($right['kind'], $left['kind']);
        });

        return $events;
    }

    /**
     * @return Collection<int, Exam>
     */
    public function exams(int $schoolId, int $sectionId, int $termId, int $examTypeId): Collection
    {
        return Exam::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->where('academic_term_id', $termId)
            ->where('exam_type_id', $examTypeId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, MarksRelease>
     */
    public function coveringReleases(int $schoolId, int $sectionId, int $termId, int $examTypeId): Collection
    {
        return MarksRelease::query()
            ->where('marks_releases.school_id', $schoolId)
            ->whereIn('marks_releases.status', [MarksRelease::STATUS_RELEASED, MarksRelease::STATUS_REOPENED])
            ->whereNotNull('marks_releases.released_at')
            ->whereHas('exam', function ($query) use ($schoolId, $termId, $examTypeId): void {
                $query->where('school_id', $schoolId)
                    ->where('exam_type_id', $examTypeId)
                    ->where('academic_term_id', $termId)
                    ->whereNull('deleted_at');
            })
            ->where(function ($query) use ($schoolId, $sectionId): void {
                $query->where('marks_releases.scope', 'school')
                    ->orWhereHas('exam', fn ($exam) => $exam
                        ->where('school_id', $schoolId)
                        ->where('section_id', $sectionId))
                    ->orWhereHas('standardLink', fn ($link) => $link
                        ->where('school_id', $schoolId)
                        ->where('section_id', $sectionId));
            })
            ->orderByDesc('marks_releases.released_at')
            ->orderByDesc('marks_releases.id')
            ->get();
    }

    /**
     * @return list<int>
     */
    private function examIds(int $schoolId, int $sectionId, int $termId, int $examTypeId): array
    {
        return $this->exams($schoolId, $sectionId, $termId, $examTypeId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function token(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * @return array{
     *     kind: string,
     *     title: string,
     *     at: CarbonInterface,
     *     who: string,
     *     role: ?string,
     *     detail: ?string,
     *     note: bool,
     *     subject_id: ?int,
     *     student_id: ?int,
     *     old: ?string,
     *     new: ?string
     * }
     */
    private function event(
        string $kind,
        string $title,
        CarbonInterface $at,
        ?User $actor,
        ?string $detail,
        bool $note,
        ?int $subjectId,
        ?int $studentId,
        ?string $old,
        ?string $new,
    ): array {
        return [
            'kind' => $kind,
            'title' => $title,
            'at' => $at,
            'who' => $actor !== null ? trim((string) $actor->name) : '',
            'role' => $this->role($actor),
            'detail' => $detail,
            'note' => $note,
            'subject_id' => $subjectId,
            'student_id' => $studentId,
            'old' => $old,
            'new' => $new,
        ];
    }

    private function role(?User $actor): ?string
    {
        if ($actor === null) {
            return null;
        }

        return match ((int) $actor->usergroup_id) {
            1, 3 => 'Admin',
            4 => 'Head teacher',
            5 => 'Teacher',
            default => null,
        };
    }
}
