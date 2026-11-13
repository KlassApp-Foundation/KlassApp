<?php

namespace App\Services\Results;

final class ResultCell
{
    public const MARKED = 'marked';

    public const MISSING = 'missing';

    public const ABSENT = 'absent';

    /**
     * What a person sees. The number stays out of the label when the mark
     * was never entered or the learner was absent: the total still uses 0.
     */
    public function sheetLabel(): string
    {
        if ($this->status === self::ABSENT) {
            return 'ABS';
        }
        if ($this->status === self::MISSING || $this->mark === null) {
            return 'Missing';
        }
        $rounded = round($this->mark, 2);
        if (abs($rounded - (int) $rounded) < 0.001) {
            return (string) (int) $rounded;
        }

        return rtrim(rtrim(number_format($rounded, 2, '.', ''), '0'), '.');
    }

    /**
     * The mark printed on a report. A number stays a number. Absent is ABS.
     * A mark that was never entered is Missing, and the total still uses 0.
     */
    public static function printedMark(mixed $source): string
    {
        if ($source instanceof self) {
            if ($source->status === self::ABSENT) {
                return 'ABS';
            }
            if ($source->status === self::MISSING || $source->mark === null) {
                return 'Missing';
            }

            return (string) (int) floor($source->mark);
        }

        if ($source instanceof \App\Models\Academics\Marks) {
            if ($source->isAbsent()) {
                return 'ABS';
            }
            if ($source->marks === null) {
                return 'Missing';
            }

            return (string) (int) floor((float) $source->marks);
        }

        return 'Missing';
    }

    public function __construct(
        public readonly int $subjectId,
        public readonly ?float $mark,
        public readonly ?string $grade,
        public readonly ?int $points,
        public readonly string $status,
    ) {
    }
}
