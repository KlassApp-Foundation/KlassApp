<?php

namespace App\Services\Results;

use App\Models\Academics\ExamType;

/**
 * Whether an exam type counts toward a term total.
 * Settings > Exam types stores each toggle as the strings "0" and "1".
 * A non-empty string is truthy in PHP, so the saved value is read as a
 * boolean. A type with no school preference uses the global flag.
 */
final class ExamTypeContribution
{
    /**
     * @param array<int|string, mixed>|null $preferences
     */
    public function contributes(ExamType $type, ?array $preferences): bool
    {
        if (is_array($preferences)) {
            foreach ($preferences as $key => $value) {
                if ((int) $key === (int) $type->id) {
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        return (bool) $type->contributes_to_report_total;
    }
}
