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
 * A whole-school fee is one row with applies_school_wide, charged to every
 * class. Older whole-school fees are still one row per level (section_id
 * null); a student is charged that level-wide row for their own standard.
 * A school-wide row of the same name replaces those level copies so the
 * student is not charged twice. The student is also charged the fees on
 * their own section and — when that section is a stream such as
 * "Primary One A" — the base class section fee. Fees for other sections
 * and other streams at the same level are not included.
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

        $rows = FeesCategories::query()
            ->with('classes')
            ->where('school_id', $schoolId)
            ->where(function ($query) use ($academicTermId) {
                if ($academicTermId) {
                    $query->where('academic_term_id', $academicTermId)
                        ->orWhereNull('academic_term_id');
                } else {
                    $query->whereNull('academic_term_id');
                }
            })
            ->where(function ($query) use ($link, $sectionIds, $schoolId) {
                $query->where('applies_school_wide', true)
                    ->orWhere(function ($level) use ($link, $sectionIds) {
                        $level->where('applies_school_wide', false)
                            ->where('standard_id', $link->standard_id)
                            ->where(function ($sections) use ($sectionIds) {
                                $sections->whereNull('section_id');
                                if ($sectionIds !== []) {
                                    $sections->orWhereIn('section_id', $sectionIds);
                                }
                            });
                    });
                if ($sectionIds !== []) {
                    $query->orWhereHas('classes', function ($classes) use ($sectionIds, $schoolId) {
                        $classes->where('sections.school_id', $schoolId)
                            ->whereIn('sections.id', $sectionIds);
                    });
                }
            })
            ->orderBy('id')
            ->get()
            ->unique('id')
            ->values();

        $wideNames = $rows
            ->filter(fn (FeesCategories $fee) => (bool) $fee->applies_school_wide)
            ->map(fn (FeesCategories $fee) => mb_strtolower((string) $fee->name))
            ->unique()
            ->all();

        if ($wideNames !== []) {
            $rows = $rows->reject(function (FeesCategories $fee) use ($wideNames) {
                return ! $fee->applies_school_wide
                    && $fee->section_id === null
                    && $fee->classes->isEmpty()
                    && in_array(mb_strtolower((string) $fee->name), $wideNames, true);
            })->values();
        }

        return new Collection($rows->all());
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
