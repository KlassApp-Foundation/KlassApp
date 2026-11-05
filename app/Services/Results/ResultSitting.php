<?php

namespace App\Services\Results;

final class ResultSitting
{
    /**
     * @param list<array{id: int, name: string, code: string, submission: string}> $subjects
     * @param list<ResultStudent> $students
     * @param string $periodKey calendar month `Y-m` when a non-contributing type is split, otherwise empty
     * @param string $periodLabel month name for that sitting, otherwise empty
     */
    public function __construct(
        public readonly int $examTypeId,
        public readonly string $name,
        public readonly string $code,
        public readonly bool $contributesToReport,
        public readonly array $subjects,
        public readonly array $students,
        public readonly string $periodKey = '',
        public readonly string $periodLabel = '',
    ) {
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
