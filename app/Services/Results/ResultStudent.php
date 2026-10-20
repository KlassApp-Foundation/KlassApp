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
}
