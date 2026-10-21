<?php

namespace App\Onboarding\Steps;

use App\Models\School;
use App\Models\SchoolDetail;

/**
 * Records that Toshi v2 has already shown seeded classes or subjects.
 *
 * Category seeding creates those rows during the academic-year save, which
 * makes the steps look finished before anyone has seen them. The review flag
 * is a school_details row, so the chat can ask once and then move on without
 * a new column. A finished school is treated as already reviewed.
 */
class OnboardingStepReview
{
    public static function reviewed(School $school, string $step): bool
    {
        if ($school->onboarding_finished_at !== null) {
            return true;
        }

        return SchoolDetail::query()
            ->where('school_id', $school->id)
            ->where('meta_key', self::metaKey($step))
            ->where('meta_value', '1')
            ->exists();
    }

    public static function mark(School $school, string $step): void
    {
        SchoolDetail::query()->updateOrCreate(
            [
                'school_id' => $school->id,
                'meta_key' => self::metaKey($step),
            ],
            ['meta_value' => '1']
        );
    }

    private static function metaKey(string $step): string
    {
        return 'onboarding_v2_reviewed_'.$step;
    }
}
