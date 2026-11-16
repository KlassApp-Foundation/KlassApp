<?php

namespace App\Services\Reports;

use App\Contracts\Results\GradingScale;
use App\Models\MarksRelease;
use App\Services\Results\ResultsPolicy;

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
}
