<?php

namespace App\Helpers;

use App\Models\User;

/**
 * Role → post-auth dashboard paths. Keep Google OAuth, password login,
 * guest middleware, and impersonation stop in sync via this helper.
 */
class AuthRedirectHelper
{
    public static function dashboardPathForUser(?User $user): string
    {
        if ($user === null) {
            return '/admin/dashboard';
        }

        return self::dashboardPathForUsergroup((int) $user->usergroup_id);
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
            10 => '/receptionist/dashboard',
            11 => '/accountant/dashboard',
            default => '/admin/dashboard',
        };
    }
}
