<?php

namespace App\Services\Results;

use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\School;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
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
     * Quick-choice role for a real exam type. Unknown types stay null so a
     * school-made set is not treated as Beginning, Mid, or End of Term.
     */
    public function sittingRole(?string $name, ?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));
        if (in_array($code, [self::END_OF_TERM_CODE, 'END'], true)) {
            return 'end';
        }
        if ($code === 'MID') {
            return 'mid';
        }
        if ($code === 'BOT') {
            return 'begin';
        }

        $normalized = strtolower(str_replace(['-', '_'], ' ', trim((string) $name)));
        $normalized = trim((string) preg_replace('/\s+/', ' ', $normalized));
        if (str_contains($normalized, 'end of term')) {
            return 'end';
        }
        if (str_contains($normalized, 'mid term') || str_contains($normalized, 'midterm')) {
            return 'mid';
        }
        if (str_contains($normalized, 'beginning of term')) {
            return 'begin';
        }

        return null;
    }

    /**
     * The saved mix, as the report line "Made up of: …". Names come from the
     * school's exam types. No name is invented for an id the school cannot see.
     *
     * @param  array<int|string, mixed>  $composition
     * @param  array<int, string>  $namesById
     */
    public function madeUpOf(array $composition, array $namesById): string
    {
        $parts = $this->weightedParts($composition, $namesById);
        if ($parts === []) {
            return '';
        }

        return 'Made up of: '.implode(' · ', array_map(
            fn (array $part): string => $part['name'].' '.$this->percentText($part['weight']),
            $parts,
        ));
    }

    /**
     * @param  array<int|string, mixed>  $composition
     * @param  array<int, string>  $namesById
     * @return array{parts: list<array{name: string, weight: float}>, total: float, adds_up: bool, aria: string}
     */
    public function mixSummary(array $composition, array $namesById): array
    {
        $parts = $this->weightedParts($composition, $namesById);
        $total = array_sum(array_map(fn (array $part): float => $part['weight'], $parts));
        $addsUp = $parts !== [] && abs($total - 100.0) < 0.011;

        return [
            'parts' => $parts,
            'total' => $total,
            'adds_up' => $addsUp,
            'aria' => $parts === []
                ? 'No exam sets count on the end-of-term report'
                : implode(', ', array_map(
                    fn (array $part): string => $part['name'].' '.$this->percentText($part['weight']).' percent',
                    $parts,
                )),
        ];
    }

    /**
     * Which quick choice matches the saved mix, if any.
     *
     * @param  iterable<int, ExamType>  $types
     * @param  array<int|string, mixed>  $composition
     */
    public function matchingPreset(iterable $types, array $composition): ?string
    {
        $begin = [];
        $mid = [];
        $end = [];
        $otherOn = false;

        foreach ($types as $type) {
            $id = (int) $type->id;
            $settings = $composition[(string) $id] ?? $composition[$id] ?? null;
            if (! is_array($settings) || ! $this->isOn($settings['include'] ?? false)) {
                continue;
            }
            $weight = is_numeric($settings['weight'] ?? null) ? (float) $settings['weight'] : 0.0;
            $role = $this->sittingRole($type->name, $type->code);
            if ($role === 'begin') {
                $begin[] = $weight;
            } elseif ($role === 'mid') {
                $mid[] = $weight;
            } elseif ($role === 'end') {
                $end[] = $weight;
            } else {
                $otherOn = true;
            }
        }

        $near = fn (float $value, float $target): bool => abs($value - $target) < 0.011;
        $sum = fn (array $weights): float => (float) array_sum($weights);

        if (! $otherOn && $begin === [] && $mid === [] && $end !== [] && $near($sum($end), 100.0)) {
            return 'end';
        }
        if (! $otherOn && $begin === [] && $mid !== [] && $end !== [] && $near($sum($mid), 30.0) && $near($sum($end), 70.0)) {
            return 'mid70';
        }

        $named = array_merge($begin, $mid, $end);
        if ($otherOn || $named === [] || ! $near($sum($named), 100.0)) {
            return null;
        }

        $expected = $this->equalWeights(count($named));
        foreach ($expected as $index => $weight) {
            if (! $near((float) $named[$index], $weight)) {
                return null;
            }
        }

        return 'equal';
    }

    /**
     * One row per exam type this school can see. The date is the latest
     * release of that type in this school, or the set is not released yet.
     *
     * @return list<array{id: int, name: string, status: string, released: bool}>
     */
    public function ownReports(int $schoolId): array
    {
        $types = ExamType::visibleToSchool($schoolId)->orderBy('id')->get(['id', 'name']);
        $released = $this->latestReleasedAt($schoolId, null, null);

        return $this->releaseRows($types, $released['dated'], $released['undated']);
    }

    /**
     * A counted set has a released numeric mark for this school.
     *
     * An absent mark is not a released learner mark here: which report mark
     * to show for ABS, and what to show when a counted set was never released,
     * are still open. This does not invent either rule.
     *
     * @param  array<int|string, mixed>  $composition
     */
    public function hasReleasedCountedMark(int $schoolId, array $composition): bool
    {
        $typeIds = [];
        foreach ($composition as $key => $settings) {
            if (! is_numeric($key) || ! is_array($settings) || ! $this->isOn($settings['include'] ?? false)) {
                continue;
            }
            $typeIds[] = (int) $key;
        }
        if ($typeIds === []) {
            return false;
        }

        return Marks::query()
            ->where('marks.school_id', $schoolId)
            ->where('marks.absent', false)
            ->whereNotNull('marks.marks')
            ->whereExists(function ($query) use ($schoolId, $typeIds): void {
                $query->selectRaw('1')
                    ->from('exams')
                    ->join('marks_releases', 'marks_releases.exam_id', '=', 'exams.id')
                    ->whereColumn('exams.id', 'marks.exam_id')
                    ->where('exams.school_id', $schoolId)
                    ->whereNull('exams.deleted_at')
                    ->whereIn('exams.exam_type_id', $typeIds)
                    ->where('marks_releases.school_id', $schoolId)
                    ->where('marks_releases.status', MarksRelease::STATUS_RELEASED);
            })
            ->exists();
    }

    /**
     * @param  list<int>|null  $sectionIds
     * @return array{dated: array<int, CarbonImmutable>, undated: array<int, true>}
     */
    private function latestReleasedAt(int $schoolId, ?array $sectionIds, ?int $termId): array
    {
        $query = DB::table('marks_releases')
            ->join('exams', 'exams.id', '=', 'marks_releases.exam_id')
            ->where('marks_releases.school_id', $schoolId)
            ->where('exams.school_id', $schoolId)
            ->where('marks_releases.status', MarksRelease::STATUS_RELEASED)
            ->whereNull('exams.deleted_at')
            ->orderByDesc('marks_releases.released_at')
            ->orderByDesc('marks_releases.id')
            ->select(['exams.exam_type_id', 'marks_releases.released_at']);

        if ($sectionIds !== null) {
            if ($sectionIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('exams.section_id', $sectionIds);
            }
        }
        if ($termId !== null) {
            $query->where('exams.academic_term_id', $termId);
        }

        $dated = [];
        $undated = [];
        foreach ($query->get() as $row) {
            $typeId = (int) $row->exam_type_id;
            if ($typeId < 1 || isset($dated[$typeId])) {
                continue;
            }
            if ($row->released_at === null || $row->released_at === '') {
                $undated[$typeId] = true;

                continue;
            }
            $dated[$typeId] = CarbonImmutable::parse((string) $row->released_at, 'UTC');
            unset($undated[$typeId]);
        }

        return ['dated' => $dated, 'undated' => $undated];
    }

    /**
     * @param  iterable<int, ExamType>  $types
     * @param  array<int, CarbonImmutable>  $dated
     * @param  array<int, true>  $undated
     * @return list<array{id: int, name: string, status: string, released: bool}>
     */
    private function releaseRows(iterable $types, array $dated, array $undated): array
    {
        $rows = [];
        foreach ($types as $type) {
            $id = (int) $type->id;
            $at = $dated[$id] ?? null;
            if ($at instanceof CarbonImmutable) {
                $status = 'Released '.$at->timezone((string) config('app.timezone'))->format('j M Y');
                $released = true;
            } elseif (isset($undated[$id])) {
                $status = 'Released';
                $released = true;
            } else {
                $status = 'Not released yet';
                $released = false;
            }
            $rows[] = [
                'id' => $id,
                'name' => (string) $type->name,
                'status' => $status,
                'released' => $released,
            ];
        }

        return $rows;
    }

    /**
     * Included sets that have a weight, heaviest first. One included set with
     * a blank weight is 100, the same rule as a report that has not been given
     * other weights.
     *
     * @param  array<int|string, mixed>  $composition
     * @param  array<int, string>  $namesById
     * @return list<array{name: string, weight: float}>
     */
    private function weightedParts(array $composition, array $namesById): array
    {
        $included = [];
        foreach ($composition as $key => $settings) {
            if (! is_array($settings) || ! $this->isOn($settings['include'] ?? false)) {
                continue;
            }
            $name = is_numeric($key) ? trim((string) ($namesById[(int) $key] ?? '')) : '';
            if ($name === '') {
                continue;
            }
            $weight = $settings['weight'] ?? null;
            $included[] = [
                'name' => $name,
                'weight' => is_numeric($weight) && (float) $weight > 0 ? (float) $weight : null,
            ];
        }

        if ($included === []) {
            return [];
        }
        if (count($included) === 1 && $included[0]['weight'] === null) {
            return [['name' => $included[0]['name'], 'weight' => 100.0]];
        }

        $weighted = array_values(array_filter($included, fn (array $row): bool => $row['weight'] !== null));
        usort($weighted, fn (array $a, array $b): int => $b['weight'] <=> $a['weight'] ?: strcasecmp($a['name'], $b['name']));

        return $weighted;
    }

    private function percentText(float $weight): string
    {
        $rounded = round($weight, 2);
        if (abs($rounded - (int) $rounded) < 0.001) {
            return ((int) $rounded).'%';
        }

        return rtrim(rtrim(number_format($rounded, 2, '.', ''), '0'), '.').'%';
    }

    /**
     * Whole weights that sum to 100. The spare points go to the first sets.
     *
     * @return list<float>
     */
    private function equalWeights(int $count): array
    {
        if ($count < 1) {
            return [];
        }

        $base = intdiv(100, $count);
        $extra = 100 - ($base * $count);
        $weights = [];
        for ($i = 0; $i < $count; $i++) {
            $weights[] = (float) ($base + ($i < $extra ? 1 : 0));
        }

        return $weights;
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
