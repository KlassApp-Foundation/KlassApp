<?php

namespace App\Services\Results;

use App\Contracts\Results\GradingScale;
use App\Helpers\GradingHelper;
use App\Models\Academics\SchoolGradingSystem;
use Illuminate\Support\Collection;

/**
 * Points from school_grading_systems for one school and standard.
 * The rows are the school's own bands. This class does not seed a curriculum.
 */
final class SchoolGradingScale implements GradingScale
{
    /** @var array<string, Collection<int, SchoolGradingSystem>> */
    private array $bands = [];

    public function pointsFor(int $schoolId, int $standardId, float $mark): ?int
    {
        $row = $this->bandFor($schoolId, $standardId, $mark);

        return $row ? GradingHelper::effectivePoints($row) : null;
    }

    public function labelFor(int $schoolId, int $standardId, float $mark): ?string
    {
        $row = $this->bandFor($schoolId, $standardId, $mark);
        if ($row === null) {
            return null;
        }

        $grade = trim((string) $row->grade);

        return $grade === '' ? null : $grade;
    }

    /**
     * Exact band first (the higher minimum wins when bands overlap).
     * A decimal that sits in a gap between integer bands stays on the
     * lower band until the next band starts. A mark above the top
     * maximum matches nothing.
     */
    private function bandFor(int $schoolId, int $standardId, float $mark): ?SchoolGradingSystem
    {
        $bands = $this->bands($schoolId, $standardId)
            ->filter(fn ($band) => $band->min_score !== null && $band->max_score !== null)
            ->sortBy(fn ($band) => [(float) $band->min_score, (int) $band->id])
            ->values();

        $exact = $bands->sortByDesc(fn ($band) => (float) $band->min_score)->first(
            fn ($band) => (float) $band->min_score <= $mark && (float) $band->max_score >= $mark
        );
        if ($exact !== null) {
            return $exact;
        }

        foreach ($bands as $index => $band) {
            if ((float) $band->min_score > $mark) {
                break;
            }
            $next = $bands[$index + 1] ?? null;
            if ($next === null || $mark >= (float) $next->min_score) {
                continue;
            }

            return $band;
        }

        return null;
    }

    /**
     * @return Collection<int, SchoolGradingSystem>
     */
    private function bands(int $schoolId, int $standardId): Collection
    {
        $key = $schoolId.':'.$standardId;
        if (! isset($this->bands[$key])) {
            $this->bands[$key] = SchoolGradingSystem::query()
                ->where('school_id', $schoolId)
                ->where('standard_id', $standardId)
                ->orderByDesc('min_score')
                ->get();
        }

        return $this->bands[$key];
    }
}
