<?php

namespace App\Services\StudentProfile;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\StudentAcademic;
use App\Models\User;

/** Where a student is enrolled now: the current academic year's class and stream, and the current term. */
class StudentEnrollment
{
    public function year(int $schoolId): ?AcademicYear
    {
        return AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();
    }

    public function term(int $schoolId, ?AcademicYear $year): ?AcademicTerm
    {
        if (! $year) {
            return null;
        }

        return AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('status', 'current')
            ->orderByDesc('id')
            ->first();
    }

    public function current(User $student, ?AcademicYear $year = null): ?StudentAcademic
    {
        $schoolId = (int) $student->school_id;
        $year ??= $this->year($schoolId);
        if (! $year) {
            return null;
        }

        return StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $student->id)
            ->where('academic_year_id', $year->id)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->with('standardLink.section')
            ->first();
    }
}
