<?php

namespace App\Support;

use App\Models\User;

/**
 * One place for "who manages this school".
 *
 * SiteAdmin (1) manages every school; SchoolAdmin (3) and SchoolSubadmin /
 * head teacher (4) manage their own. Head teachers have the same powers as a
 * school admin inside their school, never across schools. Scope is always
 * checked with school_id at the call site.
 */
final class SchoolAdmin
{
    /** @var list<int> */
    public const GROUPS = [1, 3, 4];

    public static function is(?User $user): bool
    {
        return $user !== null && in_array((int) $user->usergroup_id, self::GROUPS, true);
    }

    /**
     * A school admin or head teacher, and only for the school on their account.
     */
    public static function managesOwnSchool(?User $user, int|string|null $schoolId): bool
    {
        if ($user === null || $schoolId === null || (int) $schoolId === 0) {
            return false;
        }

        return in_array((int) $user->usergroup_id, [3, 4], true)
            && (int) $user->school_id === (int) $schoolId;
    }

    /**
     * Site admin may act in any school. A school admin or head teacher may
     * act only in their own school.
     */
    public static function managesSchool(?User $user, int|string|null $schoolId): bool
    {
        if ($user === null) {
            return false;
        }

        if ((int) $user->usergroup_id === 1) {
            return true;
        }

        return self::managesOwnSchool($user, $schoolId);
    }
}
