<?php

namespace App\Services\Toshi;

use App\Enums\ToshiMode;
use App\Models\User;

/**
 * Three UI modes for Toshi entry points a school user can see.
 *
 * - Preview (configured default): panel collapsed, Coming soon, no scripted guide, no AI.
 * - Onboarding: scripted setup guide without an AI key.
 * - Assistant: free-form + model when an AI key is configured AND mode is assistant.
 *
 * Rasta can flip a school between modes via `schools.toshi_mode` (no code change).
 * `schools.toshi_enabled` stays synced for legacy readers (1 only in assistant mode).
 */
class ToshiUiSwitch
{
    /**
     * Resolve the school's Toshi mode. Siteadmins without a school keep platform tools.
     */
    public function mode(?User $user = null): ToshiMode
    {
        $user ??= auth()->user();

        if (! $user) {
            return ToshiMode::Onboarding;
        }

        if ((int) $user->usergroup_id === 1 && $user->school_id === null) {
            return ToshiMode::Assistant;
        }

        $school = $user->school;
        if ($school === null) {
            return ToshiMode::Onboarding;
        }

        // Legacy bridge: toshi_enabled=1 always means assistant.
        if ((bool) $school->toshi_enabled) {
            return ToshiMode::Assistant;
        }

        $raw = $school->toshi_mode ?? null;
        if ($raw instanceof ToshiMode) {
            return $raw === ToshiMode::Assistant ? ToshiMode::Onboarding : $raw;
        }
        if (is_string($raw) && $raw !== '') {
            $mode = ToshiMode::tryFrom($raw) ?? ToshiMode::Onboarding;

            return $mode === ToshiMode::Assistant ? ToshiMode::Onboarding : $mode;
        }

        return ToshiMode::Onboarding;
    }

    public function previewMode(?User $user = null): bool
    {
        return $this->mode($user) === ToshiMode::Preview;
    }

    /**
     * Scripted onboarding + "Set up with Toshi" may show.
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

        if ($user->school_id === null) {
            return false;
        }

        return in_array($this->mode($user), [ToshiMode::Onboarding, ToshiMode::Assistant], true);
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

        return $this->mode($user) === ToshiMode::Assistant;
    }

    /**
     * Panel visibility — preview, onboarding, and assistant all show the panel.
     */
    public function enabled(?User $user = null): bool
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

    public function hasAiKey(): bool
    {
        $key = config('ai.providers.openai-compatible.key')
            ?: config('toshi.api_key');

        return is_string($key) && trim($key) !== '';
    }
}
