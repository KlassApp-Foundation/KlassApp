<?php

namespace App\Services\Toshi;

use App\Enums\ToshiScope;
use App\Models\School;
use App\Models\User;

/**
 * Scope-aware availability gate for the Toshi SDK path.
 *
 * School scope: unchanged per_school_gate logic (school with toshi_enabled).
 * Platform scope: usergroup 1 + config('toshi.platform_gate') capability —
 * independent of school_id / toshi_enabled.
 *
 * This is the enforcement boundary for isAvailable(). Do not use
 * getRoleCapabilities() as a substitute.
 */
class ToshiAvailabilityGate
{
    public function allows(User $user, ToshiScope $scope, ?int $schoolId = null): bool
    {
        return match ($scope) {
            ToshiScope::School => $this->allowsSchool($user, $schoolId),
            ToshiScope::Platform => $this->allowsPlatform($user),
        };
    }

    /**
     * Existing per_school_gate behaviour — extracted unchanged from
     * ToshiSdkV2Service::isAvailable().
     */
    private function allowsSchool(User $user, ?int $schoolId): bool
    {
        if (! config('toshi.per_school_gate', true)) {
            return true;
        }

        $school = $schoolId ? School::find($schoolId) : $user->school;

        if (! $school || ! $school->toshi_enabled) {
            return false;
        }

        return true;
    }

    /**
     * Platform scope: siteadmin only, plus explicit config capability.
     * Does not consult school_id or schools.toshi_enabled.
     */
    private function allowsPlatform(User $user): bool
    {
        if (! config('toshi.platform_gate.enabled', false)) {
            return false;
        }

        if ((int) $user->usergroup_id !== 1) {
            return false;
        }

        $allowedIds = config('toshi.platform_gate.user_ids', []);

        if ($allowedIds !== [] && ! in_array((int) $user->id, array_map('intval', $allowedIds), true)) {
            return false;
        }

        return true;
    }
}
