<?php

namespace App\Services\Results;

use App\Models\Academics\ExamType;

/**
 * Whether an exam type counts toward a term total.
 *
 * The results sheet used to ignore schools.exam_type_preferences and read
 * only exam_types.contributes_to_report_total. Settings > Exam types stores
 * each toggle as the strings "0" and "1". Those are read with
 * filter_var(..., FILTER_VALIDATE_BOOLEAN). (bool) "0" is already false in
 * PHP; that cast was not the bug. A type with no school preference still
 * uses the global flag.
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
