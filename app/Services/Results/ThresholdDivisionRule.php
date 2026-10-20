<?php

namespace App\Services\Results;

use App\Contracts\Results\DivisionRule;

/**
 * The division bands report cards already print.
 *
 * These thresholds are the previous inline table, moved behind DivisionRule
 * so a grading package can replace the binding. They are not a new
 * curriculum rule and they are not read from a country config file.
 */
final class ThresholdDivisionRule implements DivisionRule
{
    public function division(?int $points, int $subjectsCounted): ?string
    {
        if ($points === null) {
            return null;
        }

        if ($points <= 12) {
            return '1';
        }
        if ($points <= 24) {
            return '2';
        }
        if ($points <= 28) {
            return '3';
        }
        if ($points <= 32) {
            return '4';
        }

        return 'U';
    }
}
