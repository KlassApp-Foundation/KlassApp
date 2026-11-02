<?php

namespace App\Services\Toshi;

use App\Enums\ToshiMode;
use App\Models\School;
use App\Services\OnboardingEngine;
use Illuminate\Http\RedirectResponse;

/**
 * Real schools use the Wizard until Toshi onboarding matches it.
 *
 * Test schools (`is_test = 1`) keep the chat path. This does not read or
 * change TOSHI_ONBOARDING_V2. Wizard and Toshi v2 both complete steps through
 * OnboardingStepsService (StepRegistry steps call the same isStepComplete),
 * so a school sent from a half-finished Toshi run keeps every step already saved.
 */
class ToshiOnboardingPath
{
    public const WIZARD_FLASH = 'Guided chat setup with Toshi is coming soon. Continue with the Wizard.';

    public function openFor(?School $school): bool
    {
        return $school !== null && (int) $school->is_test === 1;
    }

    public function mustUseWizard(?School $school): bool
    {
        if ($school === null || $this->openFor($school)) {
            return false;
        }

        if ($this->mode($school) !== ToshiMode::Onboarding) {
            return false;
        }

        return OnboardingEngine::setupDestination($school, auth()->id()) !== null;
    }

    public function sendToWizard(School $school): RedirectResponse
    {
        $destination = OnboardingEngine::setupDestination($school, auth()->id());
        if ($destination === null) {
            return redirect('/admin/dashboard');
        }

        if ($this->mode($school) === ToshiMode::Onboarding && ! $this->openFor($school)) {
            $school->forceFill([
                'toshi_mode' => ToshiMode::Preview,
                'toshi_enabled' => 0,
            ])->save();
        }

        return redirect()
            ->to($destination)
            ->with('successmessage', self::WIZARD_FLASH);
    }

    private function mode(School $school): ?ToshiMode
    {
        $mode = $school->toshi_mode;

        if ($mode instanceof ToshiMode) {
            return $mode;
        }

        if (is_string($mode) && $mode !== '') {
            return ToshiMode::tryFrom($mode);
        }

        return null;
    }
}
