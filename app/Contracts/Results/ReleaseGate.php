<?php

namespace App\Contracts\Results;

use App\Models\Academics\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Whether a parent, student, or alumni may see a sitting's marks.
 *
 * Staff mark entry and staff report cards do not use this. A future
 * ResultsService can sit in front of the same interface. Grading scales,
 * report templates, and curricula stay behind their own interfaces so they
 * can become packages later.
 */
interface ReleaseGate
{
    /**
     * Sittings this person may see. A class release matches any of the
     * person's enrollments, including a past class, so a released history
     * stays visible without marking every old sitting released.
     *
     * @return list<ReleasedSitting>
     */
    public function releasedSittings(User $person): array;

    public function isReleased(User $person, Exam $exam): bool;

    /** True when any released sitting in the term covers this person. */
    public function isTermReportReleased(User $person, int $academicTermId): bool;

    /**
     * Limit an exams query to sittings released for this person.
     *
     * @param  Builder<\App\Models\Academics\Exam>  $query
     * @return Builder<\App\Models\Academics\Exam>
     */
    public function applyToExams(Builder $query, User $person): Builder;

    /**
     * Limit a marks query to sittings released for this person.
     *
     * @param  Builder<\App\Models\Academics\Marks>  $query
     * @return Builder<\App\Models\Academics\Marks>
     */
    public function applyToMarks(Builder $query, User $person): Builder;

    /**
     * Newest release first (then highest release id), not the latest
     * scheduled exam. The exam returned is one in $sectionId that has
     * this person's marks for that released sitting.
     */
    public function latestReleasedExam(User $person, int $sectionId): ?Exam;
}
