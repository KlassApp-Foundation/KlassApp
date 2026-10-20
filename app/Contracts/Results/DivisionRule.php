<?php

namespace App\Contracts\Results;

/**
 * Division label for a summed points total.
 *
 * A grading package replaces the binding. Report cards call this instead
 * of embedding a threshold table.
 */
interface DivisionRule
{
    /**
     * Null when there is no total. $subjectsCounted is available to a
     * replacement that withholds a division until every subject is in.
     */
    public function division(?int $points, int $subjectsCounted): ?string;
}
