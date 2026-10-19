<?php

namespace App\Services\Results;

final class ResultSitting
{
    /**
     * @param list<array{id: int, name: string, code: string}> $subjects
     * @param list<ResultStudent> $students
     */
    public function __construct(
        public readonly int $examTypeId,
        public readonly string $name,
        public readonly string $code,
        public readonly bool $contributesToReport,
        public readonly array $subjects,
        public readonly array $students,
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
