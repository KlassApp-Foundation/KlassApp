<?php

namespace App\Services;

use App\Events\SchoolOnboardingFinished;
use App\Models\School;

/**
 * Shared finish for the manual wizard and Toshi.
 *
 * ManualOnboardingWizard::confirmReview() used to call
 * OnboardingStepsService::markOnboardingFinished() and then update its own
 * screen. The durable part of that step lives here so both paths do it once:
 *
 * - set schools.onboarding_finished_at when it is still empty
 * - dispatch SchoolOnboardingFinished
 *
 * That confirm step does not queue a job, send a notification, clear a cache,
 * write another flag, redirect, or store a checklist row. Checklist progress
 * is derived from school data. The wizard still sets its own finished flag
 * and dispatches the Livewire event manual-onboarding-finished, which only
 * tells the Toshi panel on the same page to leave Completing Setup. Toshi
 * already leaves that mode in its own finish.
 *
 * A second call leaves the original timestamp in place and does not dispatch
 * the event again.
 */
class FinishSchoolOnboarding
{
    public function finish(School $school): bool
    {
        $school->refresh();

        if ($school->onboarding_finished_at !== null) {
            return false;
        }

        OnboardingStepsService::markOnboardingFinished($school);
        $school->refresh();

        if ($school->onboarding_finished_at === null) {
            return false;
        }

        SchoolOnboardingFinished::dispatch($school);

        return true;
    }
}
