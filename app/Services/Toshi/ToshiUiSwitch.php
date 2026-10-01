<?php

namespace App\Services\Toshi;

use App\Models\User;

/**
 * Two UI switches for Toshi entry points a school user can see.
 *
 * - Onboarding (scripted): available to every school, no AI key required.
 * - Assistant (free-form + model): only when an AI key is configured AND the
 *   school has early-access (`schools.toshi_enabled`). Siteadmins without a
 *   school see the assistant only when an AI key is set (platform scope is
 *   gated separately by ToshiAvailabilityGate).
 *
 * `enabled()` remains the panel-visibility gate and follows onboarding.
 */
class ToshiUiSwitch
{
    /**
     * Panel / scripted onboarding may show. Does not require an AI key.
     */
    public function onboardingEnabled(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        if ((int) $user->usergroup_id === 1 && $user->school_id === null) {
            return true;
        }

        return $user->school_id !== null;
    }

    /**
     * Free-form assistant (model calls) may run.
     */
    public function assistantEnabled(?User $user = null): bool
    {
        if (! $this->hasAiKey()) {
            return false;
        }

        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        if ((int) $user->usergroup_id === 1 && $user->school_id === null) {
            return true;
        }

        $school = $user->school;

        return $school !== null && (bool) $school->toshi_enabled;
    }

    /**
     * Panel visibility — scripted onboarding is available without an AI key.
     */
    public function enabled(?User $user = null): bool
    {
        return $this->onboardingEnabled($user);
    }

    public function hasAiKey(): bool
    {
        $key = config('ai.providers.openai-compatible.key')
            ?: config('toshi.api_key');

        return is_string($key) && trim($key) !== '';
    }
}
