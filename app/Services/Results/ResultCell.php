<?php

namespace App\Services\Results;

final class ResultCell
{
    public const MARKED = 'marked';

    public const MISSING = 'missing';

    public const ABSENT = 'absent';

    public function __construct(
        public readonly int $subjectId,
        public readonly ?float $mark,
        public readonly ?string $grade,
        public readonly ?int $points,
        public readonly string $status,
    ) {
    }
}
