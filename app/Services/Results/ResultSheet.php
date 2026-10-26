<?php

namespace App\Services\Results;

final class ResultSheet
{
    /**
     * @param list<array{id: int, name: string, code: string, submission: string}> $subjects
     * @param list<ResultStudent> $students
     * @param list<ResultSitting> $sittings
     */
    public function __construct(
        public readonly int $schoolId,
        public readonly int $sectionId,
        public readonly int $academicTermId,
        public readonly ?int $standardLinkId,
        public readonly ?int $examTypeId,
        public readonly array $subjects,
        public readonly array $students,
        public readonly array $sittings,
        public readonly ResultSummary $summary,
    ) {
    }

    public static function empty(ResultsQuery $query): self
    {
        return new self(
            $query->schoolId,
            $query->sectionId,
            $query->academicTermId,
            $query->standardLinkId,
            $query->examTypeId,
            [],
            [],
            [],
            new ResultSummary([], 0, 0),
        );
    }

    public function student(int $studentId): ?ResultStudent
    {
        foreach ($this->students as $student) {
            if ($student->studentId === $studentId) {
                return $student;
            }
        }

        return null;
    }
}
