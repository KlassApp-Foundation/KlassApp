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
        $row = $this->bands($schoolId, $standardId)->first(
            fn ($band) => (float) $band->min_score <= $mark && (float) $band->max_score >= $mark
        );

        return $row ? GradingHelper::effectivePoints($row) : null;
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
