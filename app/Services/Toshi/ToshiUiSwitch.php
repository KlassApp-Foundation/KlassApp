<?php

namespace App\Services\Toshi;

use App\Models\User;

/**
 * Single UI switch for every Toshi entry point a school user can see.
 *
 * Off unless an AI key is configured AND the user's school has toshi_enabled.
 * Siteadmins (usergroup 1) without a school see the UI only when an AI key is set
 * (platform scope is gated separately by ToshiAvailabilityGate).
 */
class ToshiUiSwitch
{
    public function enabled(?User $user = null): bool
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

    public function hasAiKey(): bool
    {
        $key = config('ai.providers.openai-compatible.key')
            ?: config('toshi.api_key');

        return is_string($key) && trim($key) !== '';
    }
}
