<?php

namespace App\Services\Reports;

use App\Models\Academics\Exam;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\MarksRelease;
use App\Models\StandardLink;
use App\Services\Results\ResultsPolicy;

/**
 * Copies the progressive rules onto a release, once.
 *
 * A later edit on Progressive report rules applies to the next release.
 * A reprint reads this copy. A release from before the column existed has
 * null here and is not rewritten.
 */
final class ProgressiveRulesStamp
{
    public const MODE_LIVE = 'live';

    public const MODE_HISTORICAL = 'historical';

    public const MODE_STORED = 'stored';

    /**
     * Stamp the rules used for a release that does not have them yet.
     * A second call leaves the first stamp in place.
     *
     * @return array{standards: array<string, array<string, mixed>>}
     */
    public function freeze(MarksRelease $release): array
    {
        $existing = $release->progressive_rules;
        if (is_array($existing) && is_array($existing['standards'] ?? null) && $existing['standards'] !== []) {
            return $existing;
        }

        $captured = $this->capture($release);
        $release->progressive_rules = $captured;
        $release->save();

        return $captured;
    }

    /**
     * How a card for this class and term should read its rules.
     *
     * Live: no release yet, so the school's current rules apply.
     * Stored: this release copied the rules, and the reprint uses the copy.
     * Historical: a release with no copy. Division stays on the default
     * table and ranking stays automatic, so a later edit does not move it.
     * Grade bands still come from the school, because none were stored.
     *
     * @return array{mode: string, rules: ?array<string, mixed>}
     */
    public function applied(int $schoolId, ?int $sectionId, ?int $termId, int $standardId): array
    {
        $release = $this->latestRelease($schoolId, $sectionId, $termId);
        if ($release === null) {
            return ['mode' => self::MODE_LIVE, 'rules' => null];
        }

        $rules = $this->standardRules($release, $standardId);
        if ($rules === null) {
            return ['mode' => self::MODE_HISTORICAL, 'rules' => null];
        }

        return ['mode' => self::MODE_STORED, 'rules' => $rules];
    }

    /**
     * @return array{standards: array<string, array<string, mixed>>}
     */
    public function capture(MarksRelease $release): array
    {
        $standards = [];
        foreach ($this->standardIds($release) as $standardId) {
            $standards[(string) $standardId] = $this->captureStandard((int) $release->school_id, $standardId);
        }

        return ['standards' => $standards];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function standardRules(MarksRelease $release, int $standardId): ?array
    {
        $rules = $release->progressive_rules;
        if (! is_array($rules)) {
            return null;
        }

        $standard = $rules['standards'][(string) $standardId] ?? null;

        return is_array($standard) ? $standard : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function captureStandard(int $schoolId, int $standardId): array
    {
        $rules = app(ProgressiveRules::class);
        [$basis, $higherIsBetter] = $rules->ranking($schoolId, $standardId);
        $ranges = $rules->rangesFor($schoolId, $standardId);
        if ($ranges === []) {
            $ranges = ProgressiveRules::defaultDivisionRanges();
        }

        $bands = SchoolGradingSystem::query()
            ->where('school_id', $schoolId)
            ->where('standard_id', $standardId)
            ->orderByDesc('min_score')
            ->orderBy('id')
            ->get()
            ->map(fn (SchoolGradingSystem $row) => [
                'grade' => (string) $row->grade,
                'points' => $row->points === null || $row->points === '' ? null : (int) $row->points,
                'min_score' => $row->min_score === null ? null : (float) $row->min_score,
                'max_score' => $row->max_score === null ? null : (float) $row->max_score,
                'remark' => $row->remark === null ? null : (string) $row->remark,
            ])
            ->values()
            ->all();

        return [
            'ranking_basis' => $basis,
            'higher_is_better' => $higherIsBetter,
            'division_ranges' => $ranges,
            'bands' => $bands,
        ];
    }

    /** @return list<int> */
    private function standardIds(MarksRelease $release): array
    {
        $release->loadMissing('exam');
        $exam = $release->exam;
        if ($exam === null) {
            return [];
        }

        if ($release->standard_link_id !== null) {
            $standardId = StandardLink::query()
                ->where('school_id', $release->school_id)
                ->whereKey($release->standard_link_id)
                ->value('standard_id');

            return $standardId ? [(int) $standardId] : [];
        }

        $sectionIds = Exam::query()
            ->where('school_id', $release->school_id)
            ->where('exam_type_id', $exam->exam_type_id)
            ->where('academic_term_id', $exam->academic_term_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->whereNull('deleted_at')
            ->pluck('section_id');

        return StandardLink::query()
            ->where('school_id', $release->school_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->whereIn('section_id', $sectionIds)
            ->pluck('standard_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function latestRelease(int $schoolId, ?int $sectionId, ?int $termId): ?MarksRelease
    {
        if ($sectionId === null || $sectionId < 1 || $termId === null || $termId < 1) {
            return null;
        }

        return MarksRelease::query()
            ->where('school_id', $schoolId)
            ->where('status', MarksRelease::STATUS_RELEASED)
            ->whereHas('exam', function ($query) use ($schoolId, $sectionId, $termId): void {
                $query->where('school_id', $schoolId)
                    ->where('section_id', $sectionId)
                    ->where('academic_term_id', $termId);
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Ranking value stored on a release. Anything else is rejected by ResultsPolicy.
     */
    public function rankingBasis(array $rules): string
    {
        $basis = (string) ($rules['ranking_basis'] ?? '');
        if ($basis === ResultsPolicy::BASIS_AGGREGATE || $basis === ResultsPolicy::BASIS_TOTAL) {
            return $basis;
        }

        return ResultsPolicy::BASIS_TOTAL;
    }
}
