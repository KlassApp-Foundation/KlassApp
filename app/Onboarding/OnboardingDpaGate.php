<?php

namespace App\Onboarding;

use App\Models\School;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The data processing agreement gate for onboarding writes.
 *
 * A real school must have confirmed the DPA before anything is written during
 * onboarding. Demo and test schools are exempt: they carry synthetic data and
 * the demo gates already keep them away from real schools.
 *
 * The gate binds when onboarding starts (required_at), not retroactively: a
 * school that has never started onboarding is not blocked from doing so later
 * without a prompt.
 */
final class OnboardingDpaGate
{
    /**
     * Placeholder until the approved acceptance text exists. The real version
     * identifier is configuration (ONBOARDING_DPA_VERSION), not code, so the
     * wording can be signed off without a deploy of this class.
     */
    public const PLACEHOLDER_VERSION = 'placeholder-pending-approval';

    /**
     * The gate is off unless the product is ready to ask for acceptance.
     */
    public function enabled(): bool
    {
        return (bool) config('onboarding.dpa_gate.enabled', false);
    }

    public function version(): string
    {
        $version = (string) config('onboarding.dpa_gate.version', '');

        return $version !== '' ? $version : self::PLACEHOLDER_VERSION;
    }

    /**
     * Demo and test schools are exempt. Everything else is a real school.
     */
    public function appliesTo(?School $school): bool
    {
        if (! $school instanceof School) {
            return false;
        }

        return ! (bool) $school->is_demo && ! (bool) $school->is_test;
    }

    public function requiredFor(?School $school): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        if (! $this->appliesTo($school)) {
            return false;
        }

        return $school->onboarding_dpa_required_at !== null;
    }

    public function confirmed(?School $school): bool
    {
        return $school instanceof School && $school->onboarding_dpa_confirmed_at !== null;
    }

    /**
     * Stamp the start of onboarding for a real school, once.
     */
    public function markRequired(School $school): void
    {
        if (! $this->enabled() || ! $this->appliesTo($school) || $school->onboarding_dpa_required_at !== null) {
            return;
        }

        $school->forceFill(['onboarding_dpa_required_at' => now()])->save();
    }

    /**
     * Record the acceptance. The person is recorded by the caller in the audit
     * table; the school carries when and which wording was accepted.
     */
    public function record(School $school, ?User $actor = null): void
    {
        $school->forceFill([
            'onboarding_dpa_required_at' => $school->onboarding_dpa_required_at ?? now(),
            'onboarding_dpa_confirmed_at' => now(),
            'onboarding_dpa_version' => $this->version(),
        ])->save();
    }

    /**
     * @throws ValidationException
     */
    public function assertConfirmed(School $school): void
    {
        if (! $this->requiredFor($school) || $this->confirmed($school)) {
            return;
        }

        throw ValidationException::withMessages([
            'dpa' => 'This school has not confirmed the data processing agreement. Confirm it before setting up the school.',
        ]);
    }
}
