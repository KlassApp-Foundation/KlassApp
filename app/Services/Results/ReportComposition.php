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
 * How a school's exam sets appear on one combined report.
 *
 * A set either counts toward the combined mark, with a weight, or stands
 * on its own with no weight. One combined report is stored per term for
 * now. The saved JSON keeps a `_reports` list so a second report, such as
 * a year-end report, can be added later without a new column.
 *
 * A school that has not saved a mix keeps each set's contributes flag.
 * The settings form offers a starting choice (the last set counts at 100)
 * and writes it only when an admin or head teacher saves. A release copies
 * the mix onto its own row. A later edit does not change a released report.
 */
final class ReportComposition
{
    public const END_OF_TERM_CODE = 'EOT';

    /**
     * The mix already saved for this school. Empty means the school has not
     * chosen, and totals keep each exam set's own contributes flag.
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

        return [];
    }

    /**
     * The mix for one class's combined report this term.
     *
     * When a release for this class and term already stored a mix, that copy
     * is used. Otherwise it is the school's current choice. Empty means the
     * school has not chosen, and each set's contributes flag still decides.
     *
     * @return array<string, array{include: bool, weight: float|null, locked?: bool}>
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
        $stored = $this->resolved($release->school);
        $release->report_composition = $stored !== []
            ? $stored
            : $this->legacySnapshot((int) $release->school_id);
    }

    /**
     * Whether this exam set counts toward the combined mark.
     *
     * No saved mix uses the set's own flag. A set left out of the mix stands
     * alone. A weight means it counts. An included set with no weight still
     * follows the flag, unless the mix was locked onto a release.
     */
    public function countsToward(array $composition, int $typeId, bool $catalogContributes): bool
    {
        if ($composition === []) {
            return $catalogContributes;
        }

        $settings = $composition[(string) $typeId] ?? $composition[$typeId] ?? null;
        if (! is_array($settings) || ! array_key_exists('include', $settings)) {
            return $catalogContributes;
        }
        if (($settings['locked'] ?? false) === true) {
            return $this->isOn($settings['include'] ?? false);
        }
        if (! $this->isOn($settings['include'] ?? false)) {
            return false;
        }
        if (is_numeric($settings['weight'] ?? null) && (float) $settings['weight'] > 0) {
            return true;
        }

        return $catalogContributes;
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
                ? 'No exam sets count toward the combined report'
                : implode(', ', array_map(
                    fn (array $part): string => $part['name'].' '.$this->percentText($part['weight']).' percent',
                    $parts,
                )),
        ];
    }

    /**
     * Which generated quick choice matches this mix, if any.
     * The choices come from the sets in the order given, never from fixed names.
     *
     * @param  iterable<int, ExamType>  $types
     * @param  array<int|string, mixed>  $composition
     */
    public function matchingPreset(iterable $types, array $composition): ?string
    {
        $ordered = [];
        foreach ($types as $type) {
            $ordered[] = $type;
        }
        if ($ordered === []) {
            return null;
        }

        $near = fn (float $value, float $target): bool => abs($value - $target) < 0.011;
        $last = $ordered[array_key_last($ordered)];
        $lastOnly = true;
        $weights = [];
        foreach ($ordered as $type) {
            $id = (int) $type->id;
            $settings = $composition[(string) $id] ?? $composition[$id] ?? null;
            $on = is_array($settings) && $this->isOn($settings['include'] ?? false);
            $weight = is_array($settings) && is_numeric($settings['weight'] ?? null) ? (float) $settings['weight'] : 0.0;
            if ((int) $type->id === (int) $last->id) {
                if (! $on || ! $near($weight, 100.0)) {
                    $lastOnly = false;
                }
            } elseif ($on) {
                $lastOnly = false;
            }
            if ($on) {
                $weights[] = $weight;
            }
        }
        if ($lastOnly) {
            return 'last';
        }
        if (count($weights) !== count($ordered)) {
            return null;
        }
        $expected = $this->equalWeights(count($ordered));
        foreach ($expected as $index => $weight) {
            if (! $near((float) $weights[$index], $weight)) {
                return null;
            }
        }

        return 'equal';
    }

    /**
     * Exam sets in date order. A set with no exam yet stays after the dated
     * ones, in id order. The last of this list is the starting combined set.
     *
     * @return \Illuminate\Support\Collection<int, ExamType>
     */
    public function orderedTypes(int $schoolId): \Illuminate\Support\Collection
    {
        $types = ExamType::visibleToSchool($schoolId)->get();
        $dates = DB::table('exams')
            ->where('school_id', $schoolId)
            ->whereNull('deleted_at')
            ->whereNotNull('scheduled_at')
            ->groupBy('exam_type_id')
            ->selectRaw('exam_type_id, MIN(scheduled_at) as first_at')
            ->pluck('first_at', 'exam_type_id');

        return $types->sortBy(function (ExamType $type) use ($dates): array {
            $at = $dates[$type->id] ?? null;

            return [$at === null ? 1 : 0, (string) ($at ?? ''), (int) $type->id];
        })->values();
    }

    /**
     * Starting choice for a school that has not saved a mix: the last set
     * counts at 100 and the others stand alone. This is not written until
     * someone saves it.
     *
     * @param  iterable<int, ExamType>  $types
     * @return array<string, array{include: bool, weight: float|null}>
     */
    public function suggested(iterable $types, ?int $schoolId = null): array
    {
        $ordered = [];
        foreach ($types as $type) {
            $ordered[] = $type;
        }
        if ($ordered === []) {
            return [];
        }
        $ids = array_map(fn ($type): int => (int) $type->id, $ordered);
        $withExam = [];
        if ($schoolId !== null && $schoolId > 0) {
            $withExam = DB::table('exams')
                ->where('school_id', $schoolId)
                ->whereIn('exam_type_id', $ids)
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('exam_type_id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }
        $pool = $ordered;
        if ($withExam !== []) {
            $pool = array_values(array_filter(
                $ordered,
                fn ($type): bool => in_array((int) $type->id, $withExam, true),
            ));
        }
        $lastId = (int) $pool[array_key_last($pool)]->id;
        $out = [];
        foreach ($ordered as $type) {
            $counts = (int) $type->id === $lastId;
            $out[(string) $type->id] = [
                'include' => $counts,
                'weight' => $counts ? 100.0 : null,
            ];
        }

        return $out;
    }

    /**
     * Counting sets in this class and term that have marks entered and are
     * not released. A set with no marks at all is not listed: those marks
     * are Missing, and the combined report is not blocked.
     *
     * @return list<array{id: int, name: string}>
     */
    public function blockingSets(int $schoolId, int $sectionId, int $termId): array
    {
        $mix = $this->forTermReport($schoolId, $sectionId, $termId);
        $blocked = [];
        foreach ($this->orderedTypes($schoolId) as $type) {
            if (! $this->countsToward($mix, (int) $type->id, (bool) $type->contributes_to_report_total)) {
                continue;
            }
            $examIds = DB::table('exams')
                ->where('school_id', $schoolId)
                ->where('section_id', $sectionId)
                ->where('academic_term_id', $termId)
                ->where('exam_type_id', $type->id)
                ->whereNull('deleted_at')
                ->pluck('id');
            if ($examIds->isEmpty()) {
                continue;
            }
            $markedIds = Marks::query()
                ->where('school_id', $schoolId)
                ->whereIn('exam_id', $examIds)
                ->where(function ($query): void {
                    $query->whereNotNull('marks')->orWhere('absent', true);
                })
                ->distinct()
                ->pluck('exam_id');
            if ($markedIds->isEmpty()) {
                continue;
            }
            $releasedIds = MarksRelease::query()
                ->where('school_id', $schoolId)
                ->where('status', MarksRelease::STATUS_RELEASED)
                ->whereIn('exam_id', $markedIds)
                ->pluck('exam_id');
            if ($markedIds->diff($releasedIds)->isEmpty()) {
                continue;
            }
            $blocked[] = ['id' => (int) $type->id, 'name' => (string) $type->name];
        }

        return $blocked;
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

        return $this->releaseRows($types, $released['dated'], $released['undated'], $this->displayNames($schoolId));
    }

    /**
     * Names the school typed over the starter list. The catalog row is left as it is.
     *
     * @return array<int, string>
     */
    public function displayNames(int $schoolId): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('schools', 'exam_type_labels')) {
            return [];
        }

        $raw = DB::table('schools')->where('id', $schoolId)->value('exam_type_labels');
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw)) {
            return [];
        }

        $names = [];
        foreach ($raw as $id => $name) {
            if (! is_numeric($id)) {
                continue;
            }
            $label = trim((string) $name);
            if ($label !== '') {
                $names[(int) $id] = $label;
            }
        }

        return $names;
    }

    /**
     * A counted set has a released numeric mark for this school.
     * An absent mark is not a numeric mark. Missing marks are not filled in.
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
    private function releaseRows(iterable $types, array $dated, array $undated, array $labels = []): array
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
            $label = trim((string) ($labels[$id] ?? ''));
            $rows[] = [
                'id' => $id,
                'name' => $label !== '' ? $label : (string) $type->name,
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

        return array_values(array_filter($included, fn (array $row): bool => $row['weight'] !== null));
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
     * @param  array<int|string, mixed>  $mode
     * @return array<string, array{include: bool, weight: float|null}>|null
     */
    public function fromRequest(array $include, array $weight, array $visibleIds, array $mode = []): ?array
    {
        if ($mode !== []) {
            $include = [];
            foreach ($mode as $typeId => $value) {
                $include[$typeId] = ($value === 'count' || $value === '1' || $value === 1 || $value === true) ? '1' : '0';
                if ($include[$typeId] === '0') {
                    unset($weight[$typeId]);
                }
            }
        }

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
                'include' => 'Choose at least one exam set for the combined report.',
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
        } elseif ($missing > 0 || abs($sum - 100.0) > 0.01) {
            throw ValidationException::withMessages([
                'weight' => 'The weights of the exam sets that count toward the combined report must total 100.',
            ]);
        }

        // One combined report per term today. Another entry in this list is
        // how a second report (for example a year-end report) will be stored.
        $composition['_reports'] = [
            ['name' => 'Term report'],
        ];

        return $composition;
    }

    /**
     * Copy of each set's contributes flag at the moment of release.
     * Locked so a later settings change does not move a released report.
     * Weights stay empty: several counting sets keep today's unweighted total.
     *
     * @return array<string, array{include: bool, weight: null, locked: bool}>
     */
    private function legacySnapshot(int $schoolId): array
    {
        $out = [];
        foreach (ExamType::visibleToSchool($schoolId)->get(['id', 'contributes_to_report_total']) as $type) {
            $out[(string) $type->id] = [
                'include' => (bool) $type->contributes_to_report_total,
                'weight' => null,
                'locked' => true,
            ];
        }

        if ($out === []) {
            return ['0' => ['include' => false, 'weight' => null, 'locked' => true]];
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
            if (! is_numeric($key) || ! is_array($settings) || ! array_key_exists('include', $settings)) {
                continue;
            }
            $weight = $settings['weight'] ?? null;
            $row = [
                'include' => $this->isOn($settings['include'] ?? false),
                'weight' => is_numeric($weight) ? (float) $weight : null,
            ];
            if (($settings['locked'] ?? false) === true) {
                $row['locked'] = true;
            }
            $out[(string) $key] = $row;
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
                    ->whereNull('deleted_at');
            })
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->first();

        $composition = $release?->report_composition;

        return is_array($composition) && $composition !== [] ? $composition : null;
    }
}
