<?php

namespace App\Services\Reports;

use App\Contracts\Results\DivisionRule;
use App\Contracts\Results\GradingScale;
use App\Models\MarksRelease;
use App\Models\StandardLink;
use App\Services\Results\RangeDivisionRule;
use App\Services\Results\ResultsPolicy;
use App\Services\Results\StoredGradingScale;
use App\Services\Results\ThresholdDivisionRule;

/**
 * How a blank mark is scored. The rule is stamped on the release.
 * Null keeps the older rule for that release. An unreleased card counts
 * a blank as zero. Freeze writes the new rule once and does not rewrite it.
 */
final class ReportScoring
{
    public const SKIP_BLANKS = 'skip_blanks';

    public const MISSING_AS_ZERO = 'missing_as_zero';

    public function countsMissingAsZero(int $schoolId, ?int $sectionId, ?int $termId): bool
    {
        if ($sectionId === null || $sectionId < 1 || $termId === null || $termId < 1) {
            return true;
        }

        $release = MarksRelease::query()
            ->where('school_id', $schoolId)
            ->where('status', MarksRelease::STATUS_RELEASED)
            ->whereHas('exam', function ($query) use ($schoolId, $sectionId, $termId): void {
                $query->where('school_id', $schoolId)
                    ->where('section_id', $sectionId)
                    ->where('academic_term_id', $termId);
            })
            ->orderByDesc('id')
            ->first();

        if ($release === null) {
            return true;
        }

        return (string) ($release->scoring_rule ?? '') === self::MISSING_AS_ZERO;
    }

    /**
     * Stamp the rule used for a release that does not have one yet.
     * A second call leaves the first stamp in place.
     */
    public function freeze(MarksRelease $release): string
    {
        $existing = (string) ($release->scoring_rule ?? '');
        if ($existing === self::MISSING_AS_ZERO || $existing === self::SKIP_BLANKS) {
            return $existing;
        }

        $release->scoring_rule = self::MISSING_AS_ZERO;
        $release->save();

        return self::MISSING_AS_ZERO;
    }

    public function policy(int $schoolId, int $standardId, bool $countMissingAsZero): ResultsPolicy
    {
        $rules = app(ProgressiveRules::class);
        [$basis, $higherIsBetter] = $rules->ranking($schoolId, $standardId);

        return new ResultsPolicy(
            app(GradingScale::class),
            $rules->divisionRule($schoolId, $standardId),
            $basis,
            higherIsBetter: $higherIsBetter,
            countMissingAsZero: $countMissingAsZero,
        );
    }

    /**
     * The policy for a card. A stored release uses its copy. A release with
     * no copy keeps the default division table and automatic ranking.
     * An unreleased card uses the school's current rules.
     */
    public function policyForTerm(int $schoolId, int $standardId, ?int $sectionId, ?int $termId): ResultsPolicy
    {
        $applied = app(ProgressiveRulesStamp::class)->applied($schoolId, $sectionId, $termId, $standardId);
        $countMissingAsZero = $this->countsMissingAsZero($schoolId, $sectionId, $termId);

        if ($applied['mode'] === ProgressiveRulesStamp::MODE_STORED && is_array($applied['rules'])) {
            return $this->policyFromStored($applied['rules'], $countMissingAsZero);
        }

        if ($applied['mode'] === ProgressiveRulesStamp::MODE_HISTORICAL) {
            return $this->historicalPolicy($schoolId, $standardId, $countMissingAsZero);
        }

        return $this->policy($schoolId, $standardId, $countMissingAsZero);
    }

    /**
     * The division table the progressive card uses for this class and term.
     * A stored release uses its copy. A release with no copy keeps the
     * default table. An unreleased class uses the school's current ranges.
     */
    public function divisionRuleForTerm(int $schoolId, int $standardId, ?int $sectionId, ?int $termId): DivisionRule
    {
        return $this->policyForTerm($schoolId, $standardId, $sectionId, $termId)->divisionRule;
    }

    /**
     * The division table for a class sheet. No class level means the default table.
     */
    public function divisionRuleForSection(int $schoolId, int $sectionId, int $termId, ?int $standardLinkId = null): DivisionRule
    {
        return $this->displayPolicy($schoolId, $sectionId, $termId, $standardLinkId)->divisionRule;
    }

    /**
     * The same rules as the card for this class and term: bands, division,
     * ranking, and counting a blank or an absence as 0. A screen that asks
     * for a sheet without its own policy gets this, so it cannot skip a blank
     * the card counts.
     */
    public function displayPolicy(
        int $schoolId,
        int $sectionId,
        int $termId,
        ?int $standardLinkId = null,
        bool $splitMidTermByMonth = false,
        ?DivisionRule $fallback = null,
    ): ResultsPolicy {
        $standardId = $this->standardIdForSection($schoolId, $sectionId, $standardLinkId);
        $shared = $standardId === null
            ? null
            : $this->policyForTerm($schoolId, $standardId, $sectionId, $termId);

        return new ResultsPolicy(
            $shared?->gradingScale ?? app(GradingScale::class),
            $shared?->divisionRule ?? $fallback ?? app(DivisionRule::class),
            rankingBasis: $shared?->rankingBasis ?? ResultsPolicy::BASIS_TOTAL,
            higherIsBetter: $shared?->higherIsBetter ?? true,
            splitMidTermByMonth: $splitMidTermByMonth,
            countMissingAsZero: $shared?->countMissingAsZero ?? false,
        );
    }

    /**
     * The class level for a section. Streams of one class share one standard.
     * A section tied to more than one standard has no single table here.
     */
    public function standardIdForSection(int $schoolId, ?int $sectionId, ?int $standardLinkId = null): ?int
    {
        if ($standardLinkId !== null && $standardLinkId > 0) {
            $id = StandardLink::query()
                ->where('school_id', $schoolId)
                ->whereKey($standardLinkId)
                ->value('standard_id');

            return $id ? (int) $id : null;
        }

        if ($sectionId === null || $sectionId < 1) {
            return null;
        }

        $ids = StandardLink::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $sectionId)
            ->pluck('standard_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $ids->count() === 1 ? (int) $ids->first() : null;
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function policyFromStored(array $rules, bool $countMissingAsZero): ResultsPolicy
    {
        $stamp = app(ProgressiveRulesStamp::class);
        $ranges = is_array($rules['division_ranges'] ?? null) && $rules['division_ranges'] !== []
            ? $rules['division_ranges']
            : ProgressiveRules::defaultDivisionRanges();
        $bands = is_array($rules['bands'] ?? null) ? $rules['bands'] : [];

        return new ResultsPolicy(
            new StoredGradingScale($bands),
            new RangeDivisionRule($ranges),
            $stamp->rankingBasis($rules),
            higherIsBetter: (bool) ($rules['higher_is_better'] ?? false),
            countMissingAsZero: $countMissingAsZero,
        );
    }

    /**
     * A release that predates stored rules. Saved division ranges and a
     * saved ranking are ignored, so editing them does not change this reprint.
     * Bands stay live: none were copied onto the release.
     */
    private function historicalPolicy(int $schoolId, int $standardId, bool $countMissingAsZero): ResultsPolicy
    {
        $usesPoints = \App\Models\Academics\SchoolGradingSystem::query()
            ->where('school_id', $schoolId)
            ->where('standard_id', $standardId)
            ->get()
            ->contains(fn ($row) => \App\Helpers\GradingHelper::effectivePoints($row) !== null);

        return new ResultsPolicy(
            app(GradingScale::class),
            new ThresholdDivisionRule,
            $usesPoints ? ResultsPolicy::BASIS_AGGREGATE : ResultsPolicy::BASIS_TOTAL,
            higherIsBetter: ! $usesPoints,
            countMissingAsZero: $countMissingAsZero,
        );
    }
}
