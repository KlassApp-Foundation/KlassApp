<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace App\Support;

use App\Enums\ToshiScope;

/**
 * Single Toshi feature switch.
 *
 * True only when an AI key is configured, the SDK v2 flag is on and the
 * per-school flag (Rasta decides per school) allows the assistant.
 * Platform scope ignores the school switch entirely.
 */
class Toshi
{
    public static function enabled(?\App\Models\User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        if ((int) $user->usergroup_id === 1 && $user->school_id === null) {
            return app(\App\AiAgents\ToshiSdkV2Service::class)->isAvailable($user, null, ToshiScope::Platform);
        }

        if ($user->school_id === null) {
            return false;
        }

        return app(\App\AiAgents\ToshiSdkV2Service::class)->isAvailable($user, $user->school_id);
    }
}
