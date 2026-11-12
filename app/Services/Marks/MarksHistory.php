<?php

namespace App\Services\Marks;

use App\Models\Academics\Marks;
use Illuminate\Database\Eloquent\Collection;

/**
 * Marks for one school, in the order a teacher reads a workbook:
 * academic year, term, exam set, class, subject.
 */
class MarksHistory
{
    /**
     * @return Collection<int, Marks>
     */
    public function forSchool(int $schoolId): Collection
    {
        return Marks::query()
            ->select('marks.*')
            ->join('exams', function ($join) use ($schoolId): void {
                $join->on('exams.id', '=', 'marks.exam_id')
                    ->where('exams.school_id', $schoolId)
                    ->whereNull('exams.deleted_at');
            })
            ->join('academic_years', function ($join) use ($schoolId): void {
                $join->on('academic_years.id', '=', 'exams.academic_year_id')
                    ->where('academic_years.school_id', $schoolId)
                    ->whereNull('academic_years.deleted_at');
            })
            ->join('academic_terms', function ($join) use ($schoolId): void {
                $join->on('academic_terms.id', '=', 'exams.academic_term_id')
                    ->where('academic_terms.school_id', $schoolId);
            })
            ->join('sections', function ($join) use ($schoolId): void {
                $join->on('sections.id', '=', 'exams.section_id')
                    ->where('sections.school_id', $schoolId)
                    ->whereNull('sections.deleted_at');
            })
            ->join('subjects', function ($join) use ($schoolId): void {
                $join->on('subjects.id', '=', 'marks.subject_id')
                    ->where('subjects.school_id', $schoolId)
                    ->whereNull('subjects.deleted_at');
            })
            ->where('marks.school_id', $schoolId)
            ->orderBy('academic_years.start_date')
            ->orderBy('academic_years.id')
            ->orderBy('academic_terms.starts_on')
            ->orderBy('academic_terms.id')
            ->orderByRaw('case when exams.exam_set_id is null then 1 else 0 end')
            ->orderBy('exams.exam_set_id')
            ->orderBy('sections.name')
            ->orderBy('sections.id')
            ->orderBy('subjects.name')
            ->orderBy('subjects.id')
            ->orderBy('marks.id')
            ->get();
    }
}
