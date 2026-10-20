<?php

namespace App\Services\Results;

use App\Contracts\Results\DivisionRule;
use App\Contracts\Results\GradingScale;
use InvalidArgumentException;

/**
 * How one school turns marks into grades, totals, positions and a division.
 * The scale and the division rule are replaceable. Ranking is explicit:
 * total or average, higher better, unless the caller picks another basis.
 */
final class ResultsPolicy
{
    public const BASIS_AGGREGATE = 'aggregate';

    public const BASIS_TOTAL = 'total';

    public const BASIS_AVERAGE = 'average';

    public const TIE_SHARED = 'shared';

    /**
     * @param list<string> $absentGradeLabels stored grade labels that mean absent, not zero
     * @param bool $splitMidTermByMonth keep a non-contributing exam type as one sitting per calendar month
     */
    public function __construct(
        public readonly GradingScale $gradingScale,
        public readonly DivisionRule $divisionRule,
        public readonly string $rankingBasis = self::BASIS_TOTAL,
        public readonly bool $higherIsBetter = true,
        public readonly string $tieRule = self::TIE_SHARED,
        public readonly int $roundingDecimals = 0,
        public readonly array $absentGradeLabels = [],
        public readonly bool $splitMidTermByMonth = false,
    ) {
        if (! in_array($this->rankingBasis, [self::BASIS_AGGREGATE, self::BASIS_TOTAL, self::BASIS_AVERAGE], true)) {
            throw new InvalidArgumentException('Unknown ranking basis.');
        }
        if ($this->tieRule !== self::TIE_SHARED) {
            throw new InvalidArgumentException('Unknown tie rule.');
        }
        if ($this->roundingDecimals < 0 || $this->roundingDecimals > 4) {
            throw new InvalidArgumentException('Rounding decimals must be between 0 and 4.');
        }
    }

    public function isAbsentGrade(?string $grade): bool
    {
        if ($grade === null) {
            return false;
        }
        $needle = mb_strtolower(trim($grade));
        if ($needle === '') {
            return false;
        }
        foreach ($this->absentGradeLabels as $label) {
            if (mb_strtolower(trim((string) $label)) === $needle) {
                return true;
            }
        }

        return false;
    }

    public function round(float $value): float
    {
        return round($value, $this->roundingDecimals, PHP_ROUND_HALF_UP);
    }
}
