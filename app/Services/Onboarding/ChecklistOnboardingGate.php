<?php

namespace App\Services\Onboarding;

use App\Models\School;

/**
 * The checklist proof of concept is off unless the environment flag is on
 * and the school is a demo or test school. A real school never matches.
 */
final class ChecklistOnboardingGate
{
    public function enabledFor(?School $school): bool
    {
        if (! (bool) config('toshi.checklist_onboarding_poc')) {
            return false;
        }

        if (! $school instanceof School) {
            return false;
        }

        return (bool) $school->is_demo || (bool) $school->is_test;
    }
}
