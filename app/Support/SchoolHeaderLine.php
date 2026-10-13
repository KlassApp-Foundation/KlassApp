<?php

namespace App\Support;

use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;

/**
 * The grey term that follows the school name in the top bar.
 * "Term 3, 2026" when a current term exists for the current year.
 */
class SchoolHeaderLine
{
    public static function term(?int $schoolId): string
    {
        if (! $schoolId) {
            return '';
        }

        $year = SiteHelper::getAcademicYear($schoolId);
        if (! $year) {
            return '';
        }

        $term = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('status', 'current')
            ->orderByDesc('id')
            ->first();

        $parts = array_filter([$term?->name, $year->name]);

        return implode(', ', $parts);
    }
}
