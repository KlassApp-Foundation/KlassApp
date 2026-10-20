<?php

namespace App\Services\Results;

/**
 * What a results sheet covers.
 *
 * A null exam type is a term report: contributing exam types make the
 * totals, and every exam type in the term is still listed as a sitting.
 * A set exam type is that sitting alone.
 * A stream or a student narrows the rows that come back. Positions
 * are still worked out for the whole class.
 */
final class ResultsQuery
{
    public function __construct(
        public readonly int $schoolId,
        public readonly int $sectionId,
        public readonly int $academicTermId,
        public readonly ?int $standardLinkId = null,
        public readonly ?int $examTypeId = null,
        public readonly ?int $studentId = null,
    ) {
    }
}
