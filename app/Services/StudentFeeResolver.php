<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
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
 *
 * A yearly fee is not spread across terms. It counts in its due term and once
 * in the year total. There is no separate due-term column: academic_term_id
 * is the due term when it is set, and a yearly fee with no term is due in the
 * first term of the current academic year. A termly fee with no term stays a
 * charge for every term.
 */
class StudentFeeResolver
{
    /** @var array<string, Collection<int, FeesCategories>> */
    private array $cache = [];

    /** @var array<int, ?int> */
    private array $firstTermCache = [];

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

        return $this->cache[$key] = $this->load($student, $schoolId, $academicTermId, false);
    }

    /**
     * Fees that make up the student's year total.
     *
     * Each yearly fee is included once. A termly fee with no term is included
     * once for every term in the year, because that row is the per-term charge.
     *
     * @return Collection<int, FeesCategories>
     */
    public function forYear(User $student, ?int $schoolId = null, ?int $academicYearId = null): Collection
    {
        $schoolId = $schoolId ?? ($student->school_id ? (int) $student->school_id : null);
        if ($schoolId === null || $schoolId <= 0) {
            return new Collection;
        }

        $yearId = $this->yearId($schoolId, $academicYearId);
        if ($yearId === null) {
            return new Collection;
        }

        $termIds = $this->yearTermIds($schoolId, $yearId);
        if ($termIds === []) {
            return new Collection(
                $this->load($student, $schoolId, null, true)->unique('id')->values()->all()
            );
        }

        $rows = collect();
        $seenYearly = [];
        foreach ($termIds as $termId) {
            foreach ($this->forStudent($student, $schoolId, $termId) as $fee) {
                if ($fee->duration === FeesCategories::DURATION_YEARLY) {
                    if (isset($seenYearly[(int) $fee->id])) {
                        continue;
                    }
                    $seenYearly[(int) $fee->id] = true;
                }
                $rows->push($fee);
            }
        }

        return new Collection($rows->all());
    }

    /**
     * How many times this fee is charged across the current academic year.
     * Yearly fees are always 0 or 1. They are never multiplied by the term count.
     */
    public function timesInYear(FeesCategories $fee, int $schoolId): int
    {
        $termIds = $this->yearTermIds($schoolId);
        $termId = $fee->academic_term_id ? (int) $fee->academic_term_id : null;
        if ($termId !== null && $termIds !== [] && ! in_array($termId, $termIds, true)) {
            return 0;
        }

        if ($fee->duration === FeesCategories::DURATION_YEARLY) {
            if ($termIds === []) {
                return 1;
            }
            $due = $this->dueTermId($fee, $schoolId);

            return ($due !== null && in_array($due, $termIds, true)) ? 1 : 0;
        }

        if ($termId === null) {
            return max(1, count($termIds));
        }

        return 1;
    }

    /**
     * The term a yearly fee is due. A stored academic_term_id wins. Otherwise
     * the first term of the current academic year, ordered by its start date.
     */
    public function dueTermId(FeesCategories $fee, int $schoolId): ?int
    {
        if ($fee->duration === FeesCategories::DURATION_YEARLY && $fee->academic_term_id) {
            $id = (int) $fee->academic_term_id;
            $belongs = AcademicTerm::query()
                ->where('school_id', $schoolId)
                ->whereKey($id)
                ->exists();
            if ($belongs) {
                return $id;
            }
        }

        if ($fee->duration === FeesCategories::DURATION_YEARLY) {
            return $this->firstTermId($schoolId);
        }

        return $fee->academic_term_id ? (int) $fee->academic_term_id : null;
    }

    public function feeCountsInTerm(FeesCategories $fee, ?int $academicTermId, int $schoolId): bool
    {
        if ($fee->duration !== FeesCategories::DURATION_YEARLY) {
            return true;
        }

        $due = $this->dueTermId($fee, $schoolId);

        return $academicTermId !== null && $due !== null && (int) $academicTermId === (int) $due;
    }

    /**
     * @return Collection<int, FeesCategories>
     */
    private function load(User $student, int $schoolId, ?int $academicTermId, bool $everyTerm): Collection
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
            ->when(! $everyTerm, function ($query) use ($academicTermId) {
                $query->where(function ($query) use ($academicTermId) {
                    if ($academicTermId) {
                        $query->where('academic_term_id', $academicTermId)
                            ->orWhereNull('academic_term_id');
                    } else {
                        $query->whereNull('academic_term_id');
                    }
                });
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

        if (! $everyTerm) {
            $rows = $rows->filter(
                fn (FeesCategories $fee) => $this->feeCountsInTerm($fee, $academicTermId, $schoolId)
            )->values();
        }

        return new Collection($rows->all());
    }

    private function yearId(int $schoolId, ?int $academicYearId): ?int
    {
        if ($academicYearId !== null && $academicYearId > 0) {
            $belongs = AcademicYear::query()
                ->where('school_id', $schoolId)
                ->whereKey($academicYearId)
                ->exists();

            return $belongs ? $academicYearId : null;
        }

        $year = SiteHelper::getAcademicYear($schoolId);

        return $year?->id ? (int) $year->id : null;
    }

    /**
     * @return list<int>
     */
    private function yearTermIds(int $schoolId, ?int $academicYearId = null): array
    {
        $yearId = $this->yearId($schoolId, $academicYearId);
        if ($yearId === null) {
            return [];
        }

        return array_map(
            'intval',
            array_keys(AcademicTerm::ordinalsForYear($schoolId, $yearId)['ordinals'])
        );
    }

    private function firstTermId(int $schoolId): ?int
    {
        if (array_key_exists($schoolId, $this->firstTermCache)) {
            return $this->firstTermCache[$schoolId];
        }

        $ids = $this->yearTermIds($schoolId);

        return $this->firstTermCache[$schoolId] = $ids[0] ?? null;
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
