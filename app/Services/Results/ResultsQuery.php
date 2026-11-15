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
 * Keeping mid-term months apart is a ResultsPolicy flag. It changes
 * how sittings are built, not which class or term this query covers.
 * The mark sheet sets $countApprovedSubjects: a subject that is not
 * submitted is a blank cell, and totals and positions use approved
 * subjects only. Other callers keep every stored mark.
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
        public readonly bool $countApprovedSubjects = false,
    ) {
    }
}
