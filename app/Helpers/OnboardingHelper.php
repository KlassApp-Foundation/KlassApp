<?php

namespace App\Helpers;

use App\Models\AcademicTerm;
use App\Models\FeesCategories;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\WhatsAppUser;

class OnboardingHelper
{
    /**
     * Steps that can be missing after initial school creation.
     * Matches the checks in AgentToshi::detectMissingSteps().
     */
    const STEP_LABELS = [
        'standards'       => 'Classes',
        'subjects'        => 'Subjects',
        'teachers'        => 'Teachers',
        'terms'           => 'Academic terms',
        'fees'            => 'Fee structures',
        'whatsapp_verify' => 'WhatsApp verification',
    ];

    /**
     * Return an array of step keys that are missing for the given school.
     */
    public static function getMissingSteps(int $schoolId, ?int $userId = null): array
    {
        $missing = [];

        if (!Standard::where('school_id', $schoolId)->exists()) {
            $missing[] = 'standards';
        }
        if (!Subject::where('school_id', $schoolId)->exists()) {
            $missing[] = 'subjects';
        }
        if (!Teacherlink::where('school_id', $schoolId)->exists()) {
            $missing[] = 'teachers';
        }
        if (!AcademicTerm::where('school_id', $schoolId)->exists()) {
            $missing[] = 'terms';
        }
        if (!FeesCategories::where('school_id', $schoolId)->exists()) {
            $missing[] = 'fees';
        }
        if ($userId && !WhatsAppUser::where('user_id', $userId)->exists()) {
            $missing[] = 'whatsapp_verify';
        }

        return $missing;
    }

    /**
     * Returns true if the school has any incomplete onboarding steps.
     */
    public static function hasMissingSteps(int $schoolId, ?int $userId = null): bool
    {
        return !empty(self::getMissingSteps($schoolId, $userId));
    }

    /**
     * Get human-readable labels for a list of missing step keys.
     */
    public static function getMissingLabels(array $missingKeys): array
    {
        return array_map(fn ($key) => self::STEP_LABELS[$key] ?? ucfirst($key), $missingKeys);
    }
}
