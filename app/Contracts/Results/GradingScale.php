<?php

namespace App\Contracts\Results;

/**
 * Points for a mark on a school's own grading rows.
 *
 * Default bands for a curriculum (a package) rebind this. The report
 * pipeline does not read a country scale file.
 */
interface GradingScale
{
    /**
     * Null when no band covers the mark, or the band has no points.
     */
    public function pointsFor(int $schoolId, int $standardId, float $mark): ?int;

    /**
     * The school's own band label for this mark.
     * Null when no band covers it. A package returns its own labels.
     */
    public function labelFor(int $schoolId, int $standardId, float $mark): ?string;
}
