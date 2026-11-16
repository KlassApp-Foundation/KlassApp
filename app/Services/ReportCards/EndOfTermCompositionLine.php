<?php

namespace App\Services\ReportCards;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\School;
use App\Models\Section;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The line on a combined report: "Made up of: Paper 1 40% · Paper 2 60%".
 *
 * One combined report per term. The stored mix decides which sets count.
 * With nothing stored, the sets that already count toward a total are used,
 * and a single counting set is 100. This branch does not add
 * marks_releases.report_composition. When that column is absent, the line
 * reads the school's current report_composition.
 */
final class EndOfTermCompositionLine
{
    public function lineFor(int $schoolId, bool $counts, ?int $examId = null): ?string
    {
        if (! $counts) {
            return null;
        }

        $mix = $this->compositionFor($schoolId, $examId);
        $line = $this->format(true, $mix, $this->namesFor($schoolId, $mix ?? []));
        if ($line !== null) {
            return $line;
        }

        return $this->lineFromCountingSittings($schoolId, $examId);
    }

    /**
     * A set counts when the saved mix says so. With nothing saved, the set's
     * own total flag is used. A set marked to stand alone does not count.
     *
     * @param  array<int|string, mixed>|null  $mix
     */
    public function countsToward(ExamType $type, ?array $mix): bool
    {
        $flag = (bool) $type->contributes_to_report_total;
        if ($mix === null || $this->numericRows($mix) === []) {
            return $flag;
        }

        $row = $mix[(string) $type->id] ?? $mix[$type->id] ?? null;
        if (! is_array($row)) {
            return false;
        }
        if (($row['include'] ?? true) === false) {
            return false;
        }
        $weight = $row['weight'] ?? null;
        if (is_numeric($weight) && (float) $weight > 0) {
            return true;
        }

        return $flag;
    }

    /**
     * Counting sets in this class and term that have a mark entered on an
     * exam that is not released. A set with no marks is left out.
     *
     * @return list<array{id: int, name: string}>
     */
    public function blockingSets(int $schoolId, int $sectionId, int $termId): array
    {
        $mix = $this->compositionFor($schoolId);
        $exams = Exam::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->where('academic_term_id', $termId)
            ->whereNull('deleted_at')
            ->whereNotNull('exam_type_id')
            ->orderByRaw('scheduled_at is null')
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get(['id', 'exam_type_id']);

        $types = ExamType::query()
            ->visibleToSchool($schoolId)
            ->whereIn('id', $exams->pluck('exam_type_id')->unique()->all())
            ->get()
            ->keyBy(fn (ExamType $type): int => (int) $type->id);

        // One release covers the whole exam set (every subject), not the single
        // subject exam it is anchored on. A school release covers every class.
        // A class release covers that class, including its streams.
        $releasedTypes = [];
        $releases = MarksRelease::query()
            ->where('school_id', $schoolId)
            ->where('status', MarksRelease::STATUS_RELEASED)
            ->whereHas('exam', function ($query) use ($schoolId, $termId): void {
                $query->where('school_id', $schoolId)
                    ->where('academic_term_id', $termId)
                    ->whereNull('deleted_at')
                    ->whereNotNull('exam_type_id');
            })
            ->with(['exam:id,school_id,exam_type_id,academic_term_id,section_id', 'standardLink:id,section_id'])
            ->get();
        foreach ($releases as $release) {
            $anchor = $release->exam;
            if ($anchor === null || ! $this->releaseCoversSection($release, $schoolId, $sectionId)) {
                continue;
            }
            $releasedTypes[(int) $anchor->exam_type_id] = true;
        }

        $blocked = [];
        $seen = [];
        foreach ($exams as $exam) {
            $typeId = (int) $exam->exam_type_id;
            if (isset($seen[$typeId]) || ! $types->has($typeId)) {
                continue;
            }
            $seen[$typeId] = true;
            $type = $types->get($typeId);
            if (! $this->countsToward($type, $mix) || isset($releasedTypes[$typeId])) {
                continue;
            }
            $typeExamIds = $exams
                ->filter(fn (Exam $row): bool => (int) $row->exam_type_id === $typeId)
                ->map(fn (Exam $row): int => (int) $row->id)
                ->all();
            if ($typeExamIds === []) {
                continue;
            }
            $hasMark = Marks::query()
                ->where('school_id', $schoolId)
                ->whereIn('exam_id', $typeExamIds)
                ->where(function ($query): void {
                    $query->whereNotNull('marks')->orWhere('absent', true);
                })
                ->exists();
            if ($hasMark) {
                $blocked[] = ['id' => $typeId, 'name' => (string) $type->name];
            }
        }

        return $blocked;
    }

    /**
     * A school release covers this class. A class release covers this class
     * when its link is this section or a stream of the same class.
     */
    private function releaseCoversSection(MarksRelease $release, int $schoolId, int $sectionId): bool
    {
        if ($release->scope === 'school') {
            return true;
        }
        if ($release->scope !== 'class') {
            return false;
        }

        $linkSection = (int) ($release->standardLink?->section_id ?? 0);
        if ($linkSection < 1) {
            return false;
        }
        if ($linkSection === $sectionId) {
            return true;
        }

        $sections = Section::query()->where('school_id', $schoolId)->whereIn('id', [$linkSection, $sectionId])->get();
        if ($sections->count() < 2) {
            return false;
        }

        $structure = app(\App\Services\ClassStructureService::class);
        $linkBase = (int) $structure->resolveBaseSection($sections->firstWhere('id', $linkSection))->id;
        $classBase = (int) $structure->resolveBaseSection($sections->firstWhere('id', $sectionId))->id;

        return $linkBase === $classBase;
    }

    /**
     * @param  array<int|string, mixed>|null  $composition
     * @param  array<int, string>  $namesById
     */
    public function format(bool $endOfTerm, ?array $composition, array $namesById = []): ?string
    {
        if (! $endOfTerm) {
            return null;
        }

        $parts = $this->parts(is_array($composition) ? $composition : [], $namesById);
        if ($parts === null) {
            return null;
        }

        return 'Made up of: '.implode(' · ', $parts);
    }

    public static function isEndOfTerm(?string $name, ?string $code): bool
    {
        $code = strtoupper(trim((string) $code));
        if (in_array($code, ['EOT', 'END'], true)) {
            return true;
        }

        $normalized = strtolower(str_replace(['-', '_'], ' ', trim((string) $name)));
        $normalized = trim((string) preg_replace('/\s+/', ' ', $normalized));

        return $normalized === 'eot' || str_contains($normalized, 'end of term');
    }

    public function freezeColumnExists(): bool
    {
        return Schema::hasTable('marks_releases') && Schema::hasColumn('marks_releases', 'report_composition');
    }

    /**
     * Each exam type this school can see, and whether that set is released
     * for this class and term. Another school's release is ignored.
     *
     * @param  list<int>  $sectionIds
     * @return list<array{id: int, name: string, status: string, released: bool}>
     */
    public function ownReports(int $schoolId, array $sectionIds, int $termId): array
    {
        $types = ExamType::query()->visibleToSchool($schoolId)->orderBy('id')->get(['id', 'name']);
        $sectionIds = array_values(array_unique(array_filter(array_map('intval', $sectionIds), fn (int $id): bool => $id > 0)));

        $query = DB::table('marks_releases')
            ->join('exams', 'exams.id', '=', 'marks_releases.exam_id')
            ->where('marks_releases.school_id', $schoolId)
            ->where('exams.school_id', $schoolId)
            ->where('marks_releases.status', MarksRelease::STATUS_RELEASED)
            ->where('exams.academic_term_id', $termId)
            ->whereNull('exams.deleted_at')
            ->orderByDesc('marks_releases.released_at')
            ->orderByDesc('marks_releases.id')
            ->select(['exams.exam_type_id', 'marks_releases.released_at']);

        if ($sectionIds === []) {
            $query->whereRaw('0 = 1');
        } else {
            $query->whereIn('exams.section_id', $sectionIds);
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
     * The mix captured on the release row when that column exists, otherwise
     * the school's current report_composition.
     *
     * @return array<int|string, mixed>|null
     */
    public function compositionFor(int $schoolId, ?int $examId = null): ?array
    {
        $captured = $this->capturedComposition($schoolId, $examId);
        if (is_array($captured)) {
            return $captured;
        }

        $mix = School::query()->whereKey($schoolId)->value('report_composition');
        if (is_string($mix)) {
            $mix = json_decode($mix, true);
        }

        return is_array($mix) ? $mix : null;
    }

    /**
     * @return array<int|string, mixed>|null null when nothing was captured
     */
    private function capturedComposition(int $schoolId, ?int $examId): ?array
    {
        if (! $this->freezeColumnExists() || $examId === null || $examId < 1) {
            return null;
        }

        $row = MarksRelease::query()
            ->where('school_id', $schoolId)
            ->where('exam_id', $examId)
            ->where('status', MarksRelease::STATUS_RELEASED)
            ->orderByDesc('id')
            ->first();
        if ($row === null) {
            return null;
        }

        $mix = $row->getAttribute('report_composition');
        if (is_string($mix)) {
            $mix = json_decode($mix, true);
        }

        return is_array($mix) ? $mix : null;
    }

    /**
     * @param  array<int|string, mixed>  $composition
     * @return array<int, string>
     */
    private function namesFor(int $schoolId, array $composition): array
    {
        $ids = [];
        foreach (array_keys($composition) as $key) {
            if (is_numeric($key)) {
                $ids[] = (int) $key;
            }
        }
        if ($ids === []) {
            return [];
        }

        return ExamType::query()
            ->visibleToSchool($schoolId)
            ->whereIn('id', $ids)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (ExamType $type) => [(int) $type->id => (string) $type->name])
            ->all();
    }

    /**
     * @param  array<int|string, mixed>  $composition
     * @param  array<int, string>  $namesById
     * @return list<string>|null null means the empty-composition default
     */
    private function parts(array $composition, array $namesById): ?array
    {
        if ($composition === []) {
            return null;
        }

        $included = [];
        foreach ($composition as $key => $settings) {
            if (! is_array($settings) || ($settings['include'] ?? true) === false) {
                continue;
            }
            $name = trim((string) ($settings['name'] ?? ''));
            if ($name === '' && is_numeric($key)) {
                $name = trim((string) ($namesById[(int) $key] ?? ''));
            }
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
            return null;
        }

        if (count($included) === 1 && $included[0]['weight'] === null) {
            return [$included[0]['name'].' 100%'];
        }

        $weighted = array_values(array_filter($included, fn (array $row) => $row['weight'] !== null));
        if ($weighted === []) {
            return null;
        }

        return array_map(fn (array $row) => $row['name'].' '.$this->percent($row['weight']), $weighted);
    }

    /**
     * @param  array<int|string, mixed>|null  $mix
     * @return array<int|string, mixed>
     */
    private function numericRows(?array $mix): array
    {
        if ($mix === null) {
            return [];
        }

        $rows = [];
        foreach ($mix as $key => $settings) {
            if (is_numeric($key) && is_array($settings)) {
                $rows[$key] = $settings;
            }
        }

        return $rows;
    }

    private function lineFromCountingSittings(int $schoolId, ?int $examId): ?string
    {
        if ($examId === null || $examId < 1) {
            return null;
        }

        $exam = Exam::query()->where('school_id', $schoolId)->whereKey($examId)->first();
        if ($exam === null) {
            return null;
        }

        $typeIds = Exam::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $exam->section_id)
            ->where('academic_term_id', $exam->academic_term_id)
            ->whereNull('deleted_at')
            ->whereNotNull('exam_type_id')
            ->orderByRaw('scheduled_at is null')
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->pluck('exam_type_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $startedTypeIds = Exam::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $exam->section_id)
            ->where('academic_term_id', $exam->academic_term_id)
            ->whereNull('deleted_at')
            ->whereIn('id', Marks::query()
                ->where('school_id', $schoolId)
                ->where(function ($query): void {
                    $query->whereNotNull('marks')->orWhere('absent', true);
                })
                ->select('exam_id'))
            ->pluck('exam_type_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $names = [];
        foreach ($typeIds as $typeId) {
            if (! in_array($typeId, $startedTypeIds, true)) {
                continue;
            }
            $type = ExamType::query()->visibleToSchool($schoolId)->whereKey($typeId)->first();
            if ($type !== null && (bool) $type->contributes_to_report_total) {
                $names[] = trim((string) $type->name);
            }
        }
        $names = array_values(array_filter($names, fn (string $name): bool => $name !== ''));
        if (count($names) === 1) {
            return 'Made up of: '.$names[0].' 100%';
        }
        if ($names === []) {
            return null;
        }

        return 'Made up of: '.implode(' · ', $names);
    }

    private function percent(float $weight): string
    {
        $rounded = round($weight, 2);
        if (abs($rounded - (int) $rounded) < 0.001) {
            return ((int) $rounded).'%';
        }

        return rtrim(rtrim(number_format($rounded, 2, '.', ''), '0'), '.').'%';
    }
}
