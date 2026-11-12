<?php

namespace App\Services\ReportCards;

use App\Models\Academics\ExamType;
use App\Models\MarksRelease;
use App\Models\School;
use Illuminate\Support\Facades\Schema;

/**
 * The one line on an end-of-term report: "End of Term report: End of Term 70% · Mid Term 30%".
 *
 * Weights and names come from the stored composition. An empty composition
 * is "End of Term report: End of Term 100%". One included set with a blank
 * weight is 100. Weights are never invented for the other sets.
 *
 * A released report must keep the mix used at release. That freeze is
 * marks_releases.report_composition on #1330. This branch does not add that
 * column. When it is absent, the line reads the school's current
 * report_composition, so a later settings change still changes the line.
 */
final class EndOfTermCompositionLine
{
    public function lineFor(int $schoolId, bool $endOfTerm, ?int $examId = null): ?string
    {
        if (! $endOfTerm) {
            return null;
        }

        $mix = $this->compositionFor($schoolId, $examId);

        return $this->format(true, $mix, $this->namesFor($schoolId, $mix ?? []));
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
            return 'End of Term report: End of Term 100%';
        }

        return 'End of Term report: '.implode(' · ', $parts);
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

        usort($weighted, fn (array $a, array $b) => $b['weight'] <=> $a['weight'] ?: strcasecmp($a['name'], $b['name']));

        return array_map(fn (array $row) => $row['name'].' '.$this->percent($row['weight']), $weighted);
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
