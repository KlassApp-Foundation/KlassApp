<?php

namespace App\Services\Results;

use App\Contracts\Results\DivisionRule;

/**
 * Inclusive aggregate ranges chosen by one school, or by one of its class levels.
 *
 * @phpstan-type Range array{label: string, min: ?int, max: ?int}
 */
final class RangeDivisionRule implements DivisionRule
{
    /**
     * @param  list<Range>  $ranges
     */
    public function __construct(private readonly array $ranges) {}

    public function division(?int $points, int $subjectsCounted): ?string
    {
        if ($points === null) {
            return null;
        }

        foreach ($this->ranges as $range) {
            $min = $range['min'];
            $max = $range['max'];
            $aboveMin = $min === null || $points >= $min;
            $belowMax = $max === null || $points <= $max;
            if ($aboveMin && $belowMax) {
                return $range['label'];
            }
        }

        return null;
    }
}
