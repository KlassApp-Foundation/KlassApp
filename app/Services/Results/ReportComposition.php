<?php

namespace App\Services\Results;

use App\Models\Academics\ExamType;
use App\Models\MarksRelease;
use App\Models\School;
use Illuminate\Validation\ValidationException;

/**
 * Which exam sets count on the end-of-term report, and at what weight.
 *
 * A school that has not chosen starts at End of Term only, weight 100.
 * A release copies that choice onto its own row. A later edit of the
 * school setting does not change a report that was already released.
 */
final class ReportComposition
{
    public const END_OF_TERM_CODE = 'EOT';

    /**
     * The mix a report that has not been released yet would use.
     *
     * @return array<string, array{include: bool, weight: float|null}>
     */
    public function resolved(?School $school): array
    {
        if ($school === null) {
            return [];
        }

        $stored = $school->report_composition;
        if (is_array($stored) && $stored !== []) {
            return $this->normalize($stored);
        }

        return $this->endOfTermOnly((int) $school->id);
    }

    /**
     * The mix for one class's end-of-term report.
     *
     * When that class's End of Term set is already released, the mix is the
     * one stored on the release. Otherwise it is the school's current choice.
     *
     * @return array<string, array{include: bool, weight: float|null}>
     */
    public function forTermReport(int $schoolId, int $sectionId, int $academicTermId): array
    {
        $frozen = $this->frozenForTerm($schoolId, $sectionId, $academicTermId);
        if ($frozen !== null) {
            return $this->normalize($frozen);
        }

        return $this->resolved(School::query()->find($schoolId));
    }

    /**
     * Copy the school's current mix onto the release, once.
     */
    public function freezeOn(MarksRelease $release): void
    {
        if (is_array($release->report_composition) && $release->report_composition !== []) {
            return;
        }

        $release->loadMissing('school');
        $release->report_composition = $this->resolved($release->school);
    }

    public function isEndOfTerm(?ExamType $type): bool
    {
        return $type !== null && strtoupper((string) $type->code) === self::END_OF_TERM_CODE;
    }

    /**
     * A named row uses its own include flag. A row the mix does not mention
     * stays included, so an older partial map does not drop every other set.
     *
     * @param  array<int|string, mixed>  $composition
     */
    public function includes(array $composition, int $typeId): bool
    {
        if ($composition === []) {
            return true;
        }

        $settings = $composition[(string) $typeId] ?? $composition[$typeId] ?? null;
        if (! is_array($settings) || ! array_key_exists('include', $settings)) {
            return true;
        }

        return $settings['include'] === true || $settings['include'] === 1 || $settings['include'] === '1';
    }

    /**
     * Build the mix a school admin or head teacher just submitted.
     *
     * No include and no weight means the caller did not send the mix.
     * One included set with a blank weight is stored as 100. Every other
     * included set needs a weight, and those weights must total 100.
     *
     * @param  array<int|string, mixed>  $include
     * @param  array<int|string, mixed>  $weight
     * @param  list<int>  $visibleIds
     * @return array<string, array{include: bool, weight: float|null}>|null
     */
    public function fromRequest(array $include, array $weight, array $visibleIds): ?array
    {
        if ($include === [] && $weight === []) {
            return null;
        }

        $composition = [];
        foreach ($include as $typeId => $on) {
            $typeId = (int) $typeId;
            if (! in_array($typeId, $visibleIds, true)) {
                continue;
            }
            $composition[(string) $typeId] = [
                'include' => (string) $on === '1',
                'weight' => null,
            ];
        }

        foreach ($weight as $typeId => $raw) {
            $typeId = (int) $typeId;
            if (! in_array($typeId, $visibleIds, true)) {
                continue;
            }
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }
            if (! is_numeric($raw) || (float) $raw <= 0 || (float) $raw > 100) {
                throw ValidationException::withMessages([
                    'weight' => 'Each weight is a number from 1 to 100.',
                ]);
            }
            $key = (string) $typeId;
            $composition[$key] = $composition[$key] ?? ['include' => true, 'weight' => null];
            $composition[$key]['weight'] = (float) $raw;
        }

        $included = array_filter(
            $composition,
            fn (array $row): bool => ($row['include'] ?? false) === true,
        );

        if ($included === []) {
            throw ValidationException::withMessages([
                'include' => 'Choose at least one exam set for the end-of-term report.',
            ]);
        }

        $missing = 0;
        $sum = 0.0;
        foreach ($included as $row) {
            if ($row['weight'] === null) {
                $missing++;

                continue;
            }
            $sum += (float) $row['weight'];
        }

        if (count($included) === 1 && $missing === 1) {
            $only = (string) array_key_first($included);
            $composition[$only]['weight'] = 100.0;

            return $composition;
        }

        if ($missing > 0 || abs($sum - 100.0) > 0.01) {
            throw ValidationException::withMessages([
                'weight' => 'The weights of the exam sets on the end-of-term report must total 100.',
            ]);
        }

        return $composition;
    }

    /**
     * @return array<string, array{include: bool, weight: float|null}>
     */
    private function endOfTermOnly(int $schoolId): array
    {
        $types = ExamType::visibleToSchool($schoolId)->get(['id', 'code']);
        $endOfTermIds = $types
            ->filter(fn (ExamType $type): bool => strtoupper((string) $type->code) === self::END_OF_TERM_CODE)
            ->map(fn (ExamType $type): int => (int) $type->id)
            ->values();

        if ($endOfTermIds->isEmpty()) {
            return [];
        }

        $single = $endOfTermIds->count() === 1;
        $out = [];
        foreach ($types as $type) {
            $counts = $endOfTermIds->contains((int) $type->id);
            $out[(string) $type->id] = [
                'include' => $counts,
                'weight' => $counts && $single ? 100.0 : null,
            ];
        }

        return $out;
    }

    /**
     * @param  array<int|string, mixed>  $stored
     * @return array<string, array{include: bool, weight: float|null}>
     */
    private function normalize(array $stored): array
    {
        $out = [];
        foreach ($stored as $key => $settings) {
            if (! is_array($settings)) {
                continue;
            }
            $weight = $settings['weight'] ?? null;
            $out[(string) $key] = [
                'include' => $this->isOn($settings['include'] ?? false),
                'weight' => is_numeric($weight) ? (float) $weight : null,
            ];
        }

        return $out;
    }

    private function isOn(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }

    /**
     * @return array<int|string, mixed>|null
     */
    private function frozenForTerm(int $schoolId, int $sectionId, int $academicTermId): ?array
    {
        $release = MarksRelease::query()
            ->where('school_id', $schoolId)
            ->where('status', MarksRelease::STATUS_RELEASED)
            ->whereNotNull('report_composition')
            ->whereHas('exam', function ($query) use ($schoolId, $sectionId, $academicTermId): void {
                $query->where('school_id', $schoolId)
                    ->where('section_id', $sectionId)
                    ->where('academic_term_id', $academicTermId)
                    ->whereNull('deleted_at')
                    ->whereHas('examType', function ($type): void {
                        $type->whereRaw('UPPER(code) = ?', [self::END_OF_TERM_CODE]);
                    });
            })
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->first();

        $composition = $release?->report_composition;

        return is_array($composition) && $composition !== [] ? $composition : null;
    }
}
