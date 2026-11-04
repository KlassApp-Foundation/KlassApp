<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\FeesCategories;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Services\DashboardV2DataService;
use Illuminate\Support\Collection;

/**
 * Fee structure list, per-class totals, and which classes a fee covers.
 *
 * A whole-school fee is one row (applies_school_wide). Older copies that were
 * stored once per grading tier are grouped on the list and counted once in
 * the class totals. Those copies are not deleted.
 */
class FeeStructureService
{
    /**
     * @return array{rows: list<array<string, mixed>>, totals: list<array{class: string, total: float, year_total: float}>}
     */
    public function structure(int $schoolId): array
    {
        $fees = FeesCategories::query()
            ->with(['standard', 'section', 'classes', 'term'])
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $presented = $this->present($schoolId, $fees);

        $rows = [];
        foreach ($presented as $item) {
            /** @var FeesCategories $fee */
            $fee = $item['fee'];
            $rows[] = [
                'id' => (int) $fee->id,
                'name' => (string) $fee->name,
                'amount' => (float) $fee->amount,
                'classes' => $item['classes'],
                'type' => $fee->typeLabel(),
                'duration' => $fee->durationLabel(),
                'due' => $this->dueLabel($fee, $schoolId),
            ];
        }

        return [
            'rows' => $rows,
            'totals' => $this->totals($schoolId, $presented),
        ];
    }

    /**
     * @return array{applies_to: string, section_ids: list<int>}
     */
    public function editState(FeesCategories $fee): array
    {
        $fee->loadMissing('classes');

        if ($fee->applies_school_wide || $this->legacySpreadCoversSchool($fee)) {
            return ['applies_to' => 'whole_school', 'section_ids' => []];
        }

        $selected = $fee->classes->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($selected === [] && $fee->section_id) {
            $selected = [(int) $fee->section_id];
        }
        if ($selected === [] && $fee->standard_id) {
            $selected = StandardLink::query()
                ->where('school_id', $fee->school_id)
                ->where('standard_id', $fee->standard_id)
                ->pluck('section_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return ['applies_to' => 'classes', 'section_ids' => $selected];
    }

    /**
     * @param  list<int>  $sectionIds
     */
    public function assignClasses(FeesCategories $fee, bool $wholeSchool, array $sectionIds): void
    {
        $schoolId = (int) $fee->school_id;
        $sectionIds = array_values(array_unique(array_filter(array_map('intval', $sectionIds))));

        if ($wholeSchool) {
            $fee->classes()->detach();
            $fee->forceFill([
                'applies_school_wide' => true,
                'standard_id' => null,
                'section_id' => null,
            ])->save();

            return;
        }

        $sections = Section::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $sectionIds)
            ->get();

        $fee->classes()->sync($sections->pluck('id')->map(fn ($id) => (int) $id)->all());

        $sectionId = null;
        $standardId = null;
        if ($sections->count() === 1) {
            $sectionId = (int) $sections->first()->id;
            $standardId = StandardLink::query()
                ->where('school_id', $schoolId)
                ->where('section_id', $sectionId)
                ->orderByDesc('id')
                ->value('standard_id');
            $standardId = $standardId ? (int) $standardId : null;
        }

        $fee->forceFill([
            'applies_school_wide' => false,
            'section_id' => $sectionId,
            'standard_id' => $standardId,
        ])->save();
    }

    /**
     * @param  Collection<int, FeesCategories>  $fees
     * @return list<array{fee: FeesCategories, classes: string, covers_all: bool, section_ids: list<int>, standard_id: ?int}>
     */
    private function present(int $schoolId, Collection $fees): array
    {
        // TODO: follow-up — withoutHiddenLegacyCopies hides older per-tier rows. It does not delete them.
        $visible = $this->withoutHiddenLegacyCopies($fees);
        $standardIds = Standard::query()->where('school_id', $schoolId)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $groups = [];
        foreach ($visible as $fee) {
            if (! $this->isLegacyLevelCopy($fee)) {
                continue;
            }
            $groups[$this->spreadKey($fee)][] = $fee;
        }

        $consumed = [];
        $presented = [];
        foreach ($groups as $members) {
            $covered = collect($members)->pluck('standard_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            $expected = collect($standardIds)->sort()->values()->all();
            if ($expected !== [] && $covered === $expected) {
                /** @var FeesCategories $canonical */
                $canonical = $members[0];
                $presented[] = $this->item($canonical, 'All classes', true);
                foreach ($members as $member) {
                    $consumed[(int) $member->id] = true;
                }
            }
        }

        foreach ($visible as $fee) {
            if (isset($consumed[(int) $fee->id])) {
                continue;
            }
            $presented[] = $this->item($fee, $this->classLabel($fee), (bool) $fee->applies_school_wide);
        }

        usort($presented, fn (array $a, array $b) => strcasecmp((string) $a['fee']->name, (string) $b['fee']->name));

        return $presented;
    }

    /**
     * @param  list<array{fee: FeesCategories, classes: string, covers_all: bool, section_ids: list<int>, standard_id: ?int}>  $presented
     * @return list<array{class: string, total: float}>
     */
    private function totals(int $schoolId, array $presented): array
    {
        $sections = Section::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $standardBySection = StandardLink::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('id')
            ->get()
            ->unique('section_id')
            ->mapWithKeys(fn (StandardLink $link) => [(int) $link->section_id => (int) $link->standard_id]);

        $structure = app(ClassStructureService::class);
        $resolver = app(StudentFeeResolver::class);
        $totals = [];
        foreach ($sections as $section) {
            $sectionId = (int) $section->id;
            $matchIds = [$sectionId];
            $base = $structure->resolveBaseSection($section);
            if ((int) $base->school_id === $schoolId && (int) $base->id !== $sectionId) {
                $matchIds[] = (int) $base->id;
            }
            $standardId = $standardBySection->get($sectionId);

            $sum = 0.0;
            $year = 0.0;
            foreach ($presented as $item) {
                if ($this->appliesToClass($item, $matchIds, $standardId ? (int) $standardId : null)) {
                    $amount = (float) $item['fee']->amount;
                    $sum += $amount;
                    // A class fee whose name matches a whole-school fee is added on top here.
                    $year += $amount * $resolver->timesInYear($item['fee'], $schoolId);
                }
            }

            $totals[] = [
                'class' => (string) $section->name,
                'total' => $sum,
                'year_total' => $year,
            ];
        }

        return $totals;
    }

    /**
     * @param  array{fee: FeesCategories, covers_all: bool, section_ids: list<int>, standard_id: ?int}  $item
     * @param  list<int>  $sectionIds
     */
    private function appliesToClass(array $item, array $sectionIds, ?int $standardId): bool
    {
        if ($item['covers_all']) {
            return true;
        }
        if (array_intersect($item['section_ids'], $sectionIds) !== []) {
            return true;
        }

        return $item['section_ids'] === []
            && $item['standard_id'] !== null
            && $standardId !== null
            && $item['standard_id'] === $standardId;
    }

    /**
     * Warn when a class fee reuses a whole-school fee's name. The class amount
     * is still saved and added on top of the whole-school amount.
     */
    public function sameNameStackWarning(FeesCategories $fee): ?string
    {
        if ($fee->applies_school_wide) {
            return null;
        }

        $fee->load(['classes', 'section']);
        $name = mb_strtolower(trim((string) $fee->name));
        if ($name === '') {
            return null;
        }

        $schoolWide = FeesCategories::query()
            ->where('school_id', $fee->school_id)
            ->where('applies_school_wide', true)
            ->where('id', '!=', $fee->id)
            ->orderBy('id')
            ->get()
            ->filter(fn (FeesCategories $row) => mb_strtolower(trim((string) $row->name)) === $name)
            ->values();
        if ($schoolWide->isEmpty()) {
            return null;
        }

        $termId = $fee->academic_term_id ? (int) $fee->academic_term_id : null;
        $match = $schoolWide->first(function (FeesCategories $row) use ($termId) {
            $rowTerm = $row->academic_term_id ? (int) $row->academic_term_id : null;

            return $rowTerm === $termId;
        });
        if ($match === null && $termId !== null) {
            $match = $schoolWide->first(fn (FeesCategories $row) => $row->academic_term_id === null);
        }
        $match ??= $schoolWide->first();

        $whole = (float) $match->amount;
        $total = $whole + (float) $fee->amount;
        $wholeMoney = $this->money((int) $fee->school_id, $whole);
        $totalMoney = $this->money((int) $fee->school_id, $total);

        return $match->name.' already exists as a whole-school fee ('.$wholeMoney.'). This class fee will be added on top, '.$this->classPaysPhrase($this->classNames($fee)).' '.$totalMoney.' in total.';
    }

    /**
     * @return list<string>
     */
    private function classNames(FeesCategories $fee): array
    {
        $names = $fee->classes->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn (string $name) => $name !== '')
            ->unique()
            ->values()
            ->all();
        if ($names === [] && $fee->section && trim((string) $fee->section->name) !== '') {
            $names = [trim((string) $fee->section->name)];
        }
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    /**
     * @param  list<string>  $classNames
     */
    private function classPaysPhrase(array $classNames): string
    {
        if ($classNames === []) {
            return 'so this class pays';
        }
        if (count($classNames) === 1) {
            return 'so '.$classNames[0].' pays';
        }
        if (count($classNames) === 2) {
            return 'so '.$classNames[0].' and '.$classNames[1].' each pay';
        }

        $last = array_pop($classNames);

        return 'so '.implode(', ', $classNames).' and '.$last.' each pay';
    }

    private function money(int $schoolId, float $amount): string
    {
        $currency = app(DashboardV2DataService::class)->currencyFor($schoolId);
        $formatted = number_format($amount, 0);

        return ($currency !== null && $currency !== '') ? $currency.' '.$formatted : $formatted;
    }

    private function dueLabel(FeesCategories $fee, int $schoolId): string
    {
        if ($fee->duration === FeesCategories::DURATION_YEARLY) {
            $due = app(StudentFeeResolver::class)->dueTermId($fee, $schoolId);
            if ($due) {
                $name = AcademicTerm::query()
                    ->where('school_id', $schoolId)
                    ->whereKey($due)
                    ->value('name');
                if (is_string($name) && $name !== '') {
                    return $name;
                }
            }

            return 'First term';
        }

        if ($fee->academic_term_id) {
            $name = $fee->term?->name;

            return is_string($name) && $name !== '' ? $name : 'One term';
        }

        if ($fee->duration === FeesCategories::DURATION_ONE_OFF) {
            return 'Once';
        }

        return 'Every term';
    }

    /**
     * Older per-tier copies of a fee that now has a school-wide row.
     *
     * TODO: follow-up — these rows are hidden, not deleted. A later clean-up
     * should retire or merge them once that can be done without dropping a
     * fee a class still owes. Leaving them stored is intentional for now.
     *
     * @param  Collection<int, FeesCategories>  $fees
     * @return Collection<int, FeesCategories>
     */
    private function withoutHiddenLegacyCopies(Collection $fees): Collection
    {
        $wideKeys = [];
        foreach ($fees as $fee) {
            if ($fee->applies_school_wide) {
                $wideKeys[$this->nameTermKey($fee)] = true;
            }
        }
        if ($wideKeys === []) {
            return $fees->values();
        }

        return $fees->reject(function (FeesCategories $fee) use ($wideKeys) {
            return $this->isLegacyLevelCopy($fee) && isset($wideKeys[$this->nameTermKey($fee)]);
        })->values();
    }

    private function isLegacyLevelCopy(FeesCategories $fee): bool
    {
        return ! $fee->applies_school_wide
            && $fee->section_id === null
            && $fee->standard_id !== null
            && $fee->classes->isEmpty();
    }

    private function legacySpreadCoversSchool(FeesCategories $fee): bool
    {
        if (! $this->isLegacyLevelCopy($fee)) {
            return false;
        }

        $schoolId = (int) $fee->school_id;
        $expected = Standard::query()->where('school_id', $schoolId)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($expected === []) {
            return false;
        }

        $covered = FeesCategories::query()
            ->where('school_id', $schoolId)
            ->where('name', $fee->name)
            ->where('amount', $fee->amount)
            ->where('applies_school_wide', false)
            ->whereNull('section_id')
            ->whereNotNull('standard_id')
            ->when(
                $fee->academic_term_id,
                fn ($query) => $query->where('academic_term_id', $fee->academic_term_id),
                fn ($query) => $query->whereNull('academic_term_id')
            )
            ->pluck('standard_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $covered === $expected;
    }

    /**
     * @return array{fee: FeesCategories, classes: string, covers_all: bool, section_ids: list<int>, standard_id: ?int}
     */
    private function item(FeesCategories $fee, string $classes, bool $coversAll): array
    {
        $sectionIds = $fee->classes->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($sectionIds === [] && $fee->section_id) {
            $sectionIds = [(int) $fee->section_id];
        }

        return [
            'fee' => $fee,
            'classes' => $classes,
            'covers_all' => $coversAll,
            'section_ids' => $coversAll ? [] : $sectionIds,
            'standard_id' => $coversAll ? null : ($fee->standard_id ? (int) $fee->standard_id : null),
        ];
    }

    private function classLabel(FeesCategories $fee): string
    {
        if ($fee->applies_school_wide) {
            return 'All classes';
        }
        if ($fee->classes->isNotEmpty()) {
            return $fee->classes->pluck('name')->filter()->implode(', ');
        }
        if ($fee->section) {
            return (string) $fee->section->name;
        }
        $tier = FeesCategories::tierDisplayLabel($fee->standard?->name);

        return $tier ?: 'All classes';
    }

    private function spreadKey(FeesCategories $fee): string
    {
        return implode('|', [
            mb_strtolower(trim((string) $fee->name)),
            number_format((float) $fee->amount, 2, '.', ''),
            (string) ($fee->fee_type ?: 'tuition'),
            (string) ($fee->duration ?: FeesCategories::DURATION_TERMLY),
            (string) ($fee->academic_term_id ?? ''),
        ]);
    }

    private function nameTermKey(FeesCategories $fee): string
    {
        return mb_strtolower(trim((string) $fee->name)).'|'.($fee->academic_term_id ?? '');
    }
}
