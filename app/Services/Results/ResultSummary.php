<?php

namespace App\Services\Results;

final class ResultSummary
{
    /**
     * @param list<array{subjectId: int, name: string, average: ?float, markedCount: int, expectedCount: int}> $subjects
     */
    public function __construct(
        public readonly array $subjects,
        public readonly int $subjectsWithMarks,
        public readonly int $expectedSubjects,
    ) {
    }
}
