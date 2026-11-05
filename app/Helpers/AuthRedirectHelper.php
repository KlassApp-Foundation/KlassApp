<?php

namespace App\Helpers;

use App\Models\School;
use App\Models\User;
use App\Services\OnboardingEngine;

/**
 * Role → post-auth paths. Password login, Google sign-in, the signup
 * verification handoff, and the guest middleware all read this helper.
 *
 * A school admin (usergroup 3) whose setup is unfinished goes to the earliest
 * wizard step. Every other role, and a school admin whose setup is finished,
 * still goes to that role's dashboard.
 */
class AuthRedirectHelper
{
    public static function dashboardPathForUser(?User $user): string
    {
        if ($user === null) {
            return '/admin/dashboard';
        }

        $home = self::dashboardPathForUsergroup((int) $user->usergroup_id);

        if ((int) $user->usergroup_id !== 3) {
            return $home;
        }

        $school = $user->school;
        if (! $school instanceof School) {
            return $home;
        }

        return OnboardingEngine::setupDestination($school, $user->id) ?? $home;
    }

    public static function dashboardPathForUsergroup(int $usergroupId): string
    {
        return match ($usergroupId) {
            1 => '/superadmin/dashboard',
            4 => '/subadmin/dashboard',
            5 => '/teacher/dashboard',
            6 => '/student/dashboard',
            7 => '/parent/dashboard',
            8 => '/library/dashboard',
            9 => '/alumni/dashboard',
            10 => '/receptionist/dashboard',
            11 => '/accountant/dashboard',
            // Soft-launch 1f: stock module has no routes — do not send users into a dead portal.
            12 => '/admin/dashboard',
            default => '/admin/dashboard',
        };
    }
}
