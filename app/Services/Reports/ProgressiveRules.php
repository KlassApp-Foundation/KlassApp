<?php

namespace App\Services\Reports;

use App\Contracts\Results\DivisionRule;
use App\Helpers\GradingHelper;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\ReportDivisionRange;
use App\Models\School;
use App\Models\Standard;
use App\Services\Results\RangeDivisionRule;
use App\Services\Results\ResultsPolicy;
use App\Services\Results\ThresholdDivisionRule;

/**
 * Grade points live on school_grading_systems. Division ranges and the
 * ranking basis are optional. An empty choice keeps today's automatic rule.
 */
final class ProgressiveRules
{
    public const RANK_AGGREGATE = 'aggregate';

    public const RANK_TOTAL = 'total';

    /**
     * Today's division table: 12 or less is 1, 13 to 24 is 2, 25 to 28 is 3,
     * 29 to 32 is 4, 33 or more is U.
     *
     * @return list<array{label: string, min: ?int, max: ?int}>
     */
    public static function defaultDivisionRanges(): array
    {
        return [
            ['label' => '1', 'min' => null, 'max' => 12],
            ['label' => '2', 'min' => 13, 'max' => 24],
            ['label' => '3', 'min' => 25, 'max' => 28],
            ['label' => '4', 'min' => 29, 'max' => 32],
            ['label' => 'U', 'min' => 33, 'max' => null],
        ];
    }

    public function divisionRule(int $schoolId, int $standardId): DivisionRule
    {
        $stored = $this->rangesFor($schoolId, $standardId);
        if ($stored === []) {
            return new ThresholdDivisionRule;
        }

        return new RangeDivisionRule($stored);
    }

    /**
     * @return array{0: string, 1: bool} ranking basis, then whether a higher key ranks first
     */
    public function ranking(int $schoolId, int $standardId): array
    {
        $standardBasis = Standard::query()
            ->where('school_id', $schoolId)
            ->whereKey($standardId)
            ->value('ranking_basis');
        $schoolBasis = School::query()->whereKey($schoolId)->value('ranking_basis');
        $basis = $this->normaliseBasis($standardBasis) ?? $this->normaliseBasis($schoolBasis);

        if ($basis === self::RANK_AGGREGATE) {
            return [ResultsPolicy::BASIS_AGGREGATE, false];
        }
        if ($basis === self::RANK_TOTAL) {
            return [ResultsPolicy::BASIS_TOTAL, true];
        }

        $usesPoints = SchoolGradingSystem::query()
            ->where('school_id', $schoolId)
            ->where('standard_id', $standardId)
            ->get()
            ->contains(fn ($row) => GradingHelper::effectivePoints($row) !== null);

        return $usesPoints
            ? [ResultsPolicy::BASIS_AGGREGATE, false]
            : [ResultsPolicy::BASIS_TOTAL, true];
    }

    /**
     * @return list<array{label: string, min: ?int, max: ?int}>
     */
    public function rangesFor(int $schoolId, int $standardId): array
    {
        $classRows = $this->rangeQuery($schoolId, $standardId)->get();
        $rows = $classRows->isNotEmpty()
            ? $classRows
            : $this->rangeQuery($schoolId, null)->get();

        return $rows->map(fn (ReportDivisionRange $row) => [
            'label' => (string) $row->label,
            'min' => $row->min_aggregate === null ? null : (int) $row->min_aggregate,
            'max' => $row->max_aggregate === null ? null : (int) $row->max_aggregate,
        ])->all();
    }

    private function rangeQuery(int $schoolId, ?int $standardId)
    {
        return ReportDivisionRange::query()
            ->where('school_id', $schoolId)
            ->when(
                $standardId === null,
                fn ($query) => $query->whereNull('standard_id'),
                fn ($query) => $query->where('standard_id', $standardId),
            )
            ->orderBy('position')
            ->orderBy('id');
    }

    private function normaliseBasis(mixed $value): ?string
    {
        $basis = (string) ($value ?? '');

        return in_array($basis, [self::RANK_AGGREGATE, self::RANK_TOTAL], true) ? $basis : null;
    }
}
