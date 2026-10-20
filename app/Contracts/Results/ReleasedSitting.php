<?php

namespace App\Contracts\Results;

/**
 * One exam type in one term that a person may see.
 *
 * sectionId null means the release covers every class in the school.
 * A set section is that class only. Grading scales, report templates, and
 * curricula are not part of this value — they stay behind their own interfaces.
 */
final class ReleasedSitting
{
    public function __construct(
        public int $schoolId,
        public int $examTypeId,
        public int $academicTermId,
        public ?int $sectionId,
    ) {}
}
