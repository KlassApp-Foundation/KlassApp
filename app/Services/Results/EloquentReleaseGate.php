<?php

namespace App\Services\Results;

use App\Contracts\Results\ReleasedSitting;
use App\Contracts\Results\ReleaseGate;
use App\Models\Academics\Exam;
use App\Models\MarksRelease;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Release policy backed by marks_releases.
 *
 * A sitting counts only when status is released. Scope "school" covers
 * every class of that exam type and term. Scope "class" covers the section
 * of a standard link the person was enrolled in, this year or a past year.
 */
final class EloquentReleaseGate implements ReleaseGate
{
    public function releasedSittings(User $person): array
    {
        $sittings = [];
        foreach ($this->releasedReleases($person) as $release) {
            $sitting = $this->sittingFor($release);
            if ($sitting === null) {
                continue;
            }
            $sittings[$this->key($sitting)] = $sitting;
        }

        return array_values($sittings);
    }

    public function isReleased(User $person, Exam $exam): bool
    {
        foreach ($this->releasedSittings($person) as $sitting) {
            if ($this->examMatches($sitting, $exam)) {
                return true;
            }
        }

        return false;
    }

    public function isTermReportReleased(User $person, int $academicTermId): bool
    {
        foreach ($this->releasedSittings($person) as $sitting) {
            if ($sitting->academicTermId === $academicTermId) {
                return true;
            }
        }

        return false;
    }

    public function applyToExams(Builder $query, User $person): Builder
    {
        $sittings = $this->releasedSittings($person);
        if ($sittings === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $outer) use ($sittings) {
            foreach ($sittings as $sitting) {
                $outer->orWhere(function (Builder $inner) use ($sitting) {
                    $table = $inner->getModel()->getTable();
                    $inner->where($table.'.school_id', $sitting->schoolId)
                        ->where($table.'.exam_type_id', $sitting->examTypeId)
                        ->where($table.'.academic_term_id', $sitting->academicTermId);
                    if ($sitting->sectionId !== null) {
                        $inner->where($table.'.section_id', $sitting->sectionId);
                    }
                });
            }
        });
    }

    public function applyToMarks(Builder $query, User $person): Builder
    {
        return $query->whereHas('exam', function (Builder $exam) use ($person) {
            $this->applyToExams($exam, $person);
        });
    }

    public function latestReleasedExam(User $person, int $sectionId): ?Exam
    {
        $schoolId = (int) $person->school_id;
        if ($schoolId <= 0 || $sectionId <= 0) {
            return null;
        }

        foreach ($this->releasedReleases($person) as $release) {
            $sitting = $this->sittingFor($release);
            if ($sitting === null || ($sitting->sectionId !== null && $sitting->sectionId !== $sectionId)) {
                continue;
            }

            $exam = Exam::query()
                ->where('school_id', $schoolId)
                ->where('section_id', $sectionId)
                ->where('exam_type_id', $sitting->examTypeId)
                ->where('academic_term_id', $sitting->academicTermId)
                ->whereNull('deleted_at')
                ->whereHas('marks', fn ($q) => $q->where('school_id', $schoolId)
                    ->where('section_id', $sectionId)
                    ->where('student_id', $person->id))
                ->orderByDesc('scheduled_at')
                ->orderByDesc('id')
                ->first();

            if ($exam !== null) {
                return $exam;
            }
        }

        return null;
    }

    /**
     * Released rows that cover this person, newest release first.
     *
     * @return Collection<int, MarksRelease>
     */
    private function releasedReleases(User $person): Collection
    {
        $schoolId = (int) $person->school_id;
        if ($schoolId <= 0) {
            return collect();
        }

        $linkIds = $this->enrollmentLinkIds($person, $schoolId);

        return MarksRelease::query()
            ->where('school_id', $schoolId)
            ->where('status', MarksRelease::STATUS_RELEASED)
            ->where(function ($query) use ($linkIds) {
                $query->where('scope', 'school');
                if ($linkIds !== []) {
                    $query->orWhere(function ($class) use ($linkIds) {
                        $class->where('scope', 'class')->whereIn('standard_link_id', $linkIds);
                    });
                }
            })
            ->with([
                'exam:id,school_id,exam_type_id,academic_term_id',
                'standardLink:id,section_id',
            ])
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Every class this person has been enrolled in at this school.
     *
     * @return list<int>
     */
    private function enrollmentLinkIds(User $person, int $schoolId): array
    {
        return StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $person->id)
            ->whereNotNull('standardLink_id')
            ->pluck('standardLink_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function sittingFor(MarksRelease $release): ?ReleasedSitting
    {
        $anchor = $release->exam;
        if ($anchor === null || $anchor->exam_type_id === null || $anchor->academic_term_id === null) {
            return null;
        }

        $sectionId = null;
        if ($release->scope === 'class') {
            $sectionId = $release->standardLink?->section_id;
            if ($sectionId === null && $release->standard_link_id) {
                $sectionId = StandardLink::query()->whereKey($release->standard_link_id)->value('section_id');
            }
            if ($sectionId === null) {
                return null;
            }
            $sectionId = (int) $sectionId;
        }

        return new ReleasedSitting(
            (int) $release->school_id,
            (int) $anchor->exam_type_id,
            (int) $anchor->academic_term_id,
            $sectionId,
        );
    }

    private function examMatches(ReleasedSitting $sitting, Exam $exam): bool
    {
        if ($sitting->schoolId !== (int) $exam->school_id) {
            return false;
        }
        if ($sitting->examTypeId !== (int) $exam->exam_type_id) {
            return false;
        }
        if ($sitting->academicTermId !== (int) $exam->academic_term_id) {
            return false;
        }
        if ($sitting->sectionId !== null && $sitting->sectionId !== (int) $exam->section_id) {
            return false;
        }

        return true;
    }

    private function key(ReleasedSitting $sitting): string
    {
        return implode(':', [
            $sitting->schoolId,
            $sitting->examTypeId,
            $sitting->academicTermId,
            $sitting->sectionId ?? '*',
        ]);
    }
}
