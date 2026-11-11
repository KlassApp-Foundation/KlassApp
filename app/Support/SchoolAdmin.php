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
}
