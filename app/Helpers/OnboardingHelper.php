<?php

namespace App\Helpers;

use App\Models\School;
use App\Services\OnboardingStepsService;

/**
 * Onboarding helper — delegates to OnboardingStepsService.
 *
 * Kept for backwards compatibility; new code should use
 * OnboardingStepsService directly.
 */
class OnboardingHelper
{
    const STEP_LABELS = [
        'school_name'     => 'School name',
        'curriculum'      => 'Board / Curriculum',
        'country'         => 'Country',
        'emis'            => 'EMIS / Ministry code',
        'uneb_center'     => 'UNEB centre number',
        'academic_year'   => 'Academic year',
        'standards'       => 'Classes',
        'subjects'        => 'Subjects',
        'teachers'        => 'Teachers',
        'terms'           => 'Academic terms',
        'fees'            => 'Fee structures',
        'whatsapp_verify' => 'WhatsApp verification',
        'plan_selection'  => 'Plan selection',
    ];

    public static function getMissingSteps(int $schoolId, ?int $userId = null): array
    {
        $school = School::find($schoolId);
        if (! $school) {
            return [];
        }

        $incomplete = OnboardingStepsService::incompleteSteps($school, $userId);

        return array_map(fn ($s) => $s['key'], $incomplete);
    }

    public static function hasMissingSteps(int $schoolId, ?int $userId = null): bool
    {
        $school = School::find($schoolId);
        if (! $school) {
            return false;
        }

        return OnboardingStepsService::hasBlockingIncompleteSteps($school, $userId);
    }

    public static function getMissingLabels(array $missingKeys): array
    {
        return array_map(fn ($key) => self::STEP_LABELS[$key] ?? ucfirst($key), $missingKeys);
    }
}
