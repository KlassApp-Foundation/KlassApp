<?php

namespace App\Ai\Concerns;

use App\Enums\ToshiScope;
use App\Models\User;
use App\Services\Toshi\ToshiAvailabilityGate;

/**
 * Platform-scope auth for Superadmin Toshi tools.
 *
 * Uses ToshiAvailabilityGate + ToshiScope::Platform only.
 * Never uses getRoleCapabilities() as auth.
 */
trait AuthorizesPlatformAction
{
    protected function authorizeOrMessage(?User $user): ?string
    {
        if (! $user) {
            return '❌ Authentication required.';
        }

        $gate = app(ToshiAvailabilityGate::class);

        if (! $gate->allows($user, ToshiScope::Platform)) {
            return '❌ Platform Toshi access denied.';
        }

        return null;
    }
}
