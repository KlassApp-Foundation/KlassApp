<?php

namespace App\Services\Results;

final class ResultCell
{
    public const MARKED = 'marked';

    public const MISSING = 'missing';

    public const ABSENT = 'absent';

    /** A combined mark is withheld because a weighted set has not been released. */
    public const WITHHELD = 'withheld';

    /** Saved or blank, but the subject is not approved, so the sheet leaves it out. */
    public const UNAPPROVED = 'unapproved';

    /** What a learner sees when there is no mark. Never ABS: ABS is attendance only. */
    public const DASH = '–';

    /** What a learner sees when the subject is not approved yet. Never a mark. */
    public const PENDING = 'Pending';

    /**
     * What a person sees. A missing mark and an absence are both a dash: the
     * total still counts either as 0. A subject that is not approved yet is
     * Pending and is left out of the total, the aggregate and the division.
     */
    public function sheetLabel(): string
    {
        if ($this->status === self::WITHHELD) {
            return 'Withheld';
        }
        if ($this->status === self::UNAPPROVED) {
            return self::PENDING;
        }
        if ($this->status === self::ABSENT) {
            return self::DASH;
        }
        if ($this->status === self::MISSING || $this->mark === null) {
            return self::DASH;
        }
        return ResultsPolicy::printWhole((float) $this->mark);
    }

    /**
     * The mark printed on a report. A number stays a number. Absent is ABS.
     * A mark that was never entered is Missing, and the total still uses 0.
     */
    public static function printedMark(mixed $source): string
    {
        if ($source instanceof self) {
            if ($source->status === self::WITHHELD) {
                return 'Withheld';
            }
            if ($source->status === self::UNAPPROVED) {
                return self::PENDING;
            }
            if ($source->status === self::ABSENT) {
                return self::DASH;
            }
            if ($source->status === self::MISSING || $source->mark === null) {
                return self::DASH;
            }

            return $source->sheetLabel();
        }

        if ($source instanceof \App\Models\Academics\Marks) {
            return $source->displayMark();
        }

        return 'Missing';
    }

    public function __construct(
        public readonly int $subjectId,
        public readonly ?float $mark,
        public readonly ?string $grade,
        public readonly ?int $points,
        public readonly string $status,
        public readonly bool $gap = false,
    ) {
    }

    /**
     * A counting set is incomplete when this learner has no mark or was absent.
     * A subject that is not approved still prints a dash. The gap flag keeps
     * the missing mark or the absence visible to that sentence.
     */
    public function namesIncompleteSet(): bool
    {
        return $this->gap
            || $this->status === self::MISSING
            || $this->status === self::ABSENT;
    }
}
