<?php

namespace App\Services\Results;

final class ResultStudent
{
    /**
     * @param array<int, ResultCell> $cells
     */
    public function __construct(
        public readonly int $studentId,
        public readonly string $name,
        public readonly int $standardLinkId,
        public readonly string $stream,
        public readonly int $standardId,
        public readonly array $cells,
        public readonly ?float $total,
        public readonly int $subjectsCounted,
        public readonly ?float $average,
        public readonly ?int $aggregate,
        public readonly ?string $division,
        public readonly ?int $classPosition,
        public readonly ?int $streamPosition,
        public readonly int $classOutOf,
        public readonly int $streamOutOf,
        public readonly ?float $displayTotal,
        public readonly ?float $displayAverage,
    ) {
    }

    public function cell(int $subjectId): ?ResultCell
    {
        return $this->cells[$subjectId] ?? null;
    }

    /**
     * The place already worked out for this sheet.
     * A stream filter uses that stream's pool. The whole class uses the class pool.
     */
    public function placeLabel(bool $byStream): string
    {
        $position = $byStream ? $this->streamPosition : $this->classPosition;
        $outOf = $byStream ? $this->streamOutOf : $this->classOutOf;
        if ($position === null) {
            return '—';
        }

        return $position.' of '.$outOf;
    }
}
