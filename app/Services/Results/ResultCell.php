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

    /**
     * What a person sees. The number stays out of the label when the mark
     * was never entered or the learner was absent: the total still uses 0.
     * A subject that is not approved is a dash and is left out of the total.
     */
    public function sheetLabel(): string
    {
        if ($this->status === self::WITHHELD) {
            return 'Withheld';
        }
        if ($this->status === self::UNAPPROVED) {
            return '—';
        }
        if ($this->status === self::ABSENT) {
            return 'ABS';
        }
        if ($this->status === self::MISSING || $this->mark === null) {
            return 'Missing';
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
                return '—';
            }
            if ($source->status === self::ABSENT) {
                return 'ABS';
            }
            if ($source->status === self::MISSING || $source->mark === null) {
                return 'Missing';
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
