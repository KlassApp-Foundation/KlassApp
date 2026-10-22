<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;
use App\Models\FeesCategories;
use App\Models\Section;
use App\Models\StudentAcademic;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Fees that apply to one student.
 *
 * A whole-school fee is stored once per level (one row per standard, section_id
 * null). A student is charged that level-wide row for their own standard, the
 * fees on their own section, and — when that section is a stream such as
 * "Primary One A" — the base class section fee as well. Fees for other
 * sections and other streams at the same level are not included.
 *
 * The same fee name may exist once per term. A total for one term includes
 * that term's rows and rows with no term (academic_term_id null). It does not
 * include another term's row of the same name.
 */
class StudentFeeResolver
{
    /** @var array<string, Collection<int, FeesCategories>> */
    private array $cache = [];

    /**
     * The term marked current for this school's year, or the term whose dates
     * contain today when none is marked current.
     */
    public function currentTermId(int $schoolId): ?int
    {
        if ($schoolId <= 0) {
            return null;
        }

        $year = SiteHelper::getAcademicYear($schoolId);
        if (! $year) {
            return null;
        }

        $termId = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('status', 'current')
            ->orderByDesc('id')
            ->value('id');

        if ($termId) {
            return (int) $termId;
        }

        $today = now()->toDateString();
        $fallback = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->whereDate('starts_on', '<=', $today)
            ->whereDate('ends_on', '>=', $today)
            ->orderByDesc('id')
            ->value('id');

        return $fallback ? (int) $fallback : null;
    }

    /**
     * @param  int|null  $academicTermId  The term being totaled. Null keeps only fees that are not tied to a term.
     * @return Collection<int, FeesCategories>
     */
    public function forStudent(User $student, ?int $schoolId = null, ?int $academicTermId = null): Collection
    {
        $schoolId = $schoolId ?? ($student->school_id ? (int) $student->school_id : null);
        if ($schoolId === null || $schoolId <= 0) {
            return new Collection;
        }

        $academicTermId = $academicTermId && $academicTermId > 0 ? $academicTermId : null;
        $key = $schoolId.':'.$student->id.':'.($academicTermId ?? 'none');
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        return $this->cache[$key] = $this->load($student, $schoolId, $academicTermId);
    }

    /**
     * @return Collection<int, FeesCategories>
     */
    private function load(User $student, int $schoolId, ?int $academicTermId): Collection
    {
        $academic = StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $student->id)
            ->whereIn('academic_year_id', function ($query) use ($schoolId) {
                $query->select('id')
                    ->from('academic_years')
                    ->where('school_id', $schoolId)
                    ->where('status', 1);
            })
            ->orderByDesc('id')
            ->with('standardLink.section')
            ->first();

        $link = $academic?->standardLink;
        if (! $link || (int) $link->school_id !== $schoolId || ! $link->standard_id) {
            return new Collection;
        }

        $sectionIds = $this->sectionIds($schoolId, $link->section_id ? (int) $link->section_id : null, $link->section);

        return FeesCategories::query()
            ->where('school_id', $schoolId)
            ->where('standard_id', $link->standard_id)
            ->where(function ($query) use ($sectionIds) {
                $query->whereNull('section_id');
                if ($sectionIds !== []) {
                    $query->orWhereIn('section_id', $sectionIds);
                }
            })
            ->where(function ($query) use ($academicTermId) {
                if ($academicTermId) {
                    $query->where('academic_term_id', $academicTermId)
                        ->orWhereNull('academic_term_id');
                } else {
                    $query->whereNull('academic_term_id');
                }
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * The student's section, plus the undivided base section when they are in a stream.
     *
     * @return list<int>
     */
    private function sectionIds(int $schoolId, ?int $sectionId, ?Section $section): array
    {
        if ($sectionId === null) {
            return [];
        }

        $ids = [$sectionId];
        if ($section && (int) $section->school_id === $schoolId) {
            $base = app(ClassStructureService::class)->resolveBaseSection($section);
            if ((int) $base->school_id === $schoolId) {
                $ids[] = (int) $base->id;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
