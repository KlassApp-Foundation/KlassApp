<?php

namespace App\Support;

use App\Models\User;

/**
 * Which page shell (layout) a role lives in. Used by pages every role shares, such as the
 * account pages, so each person stays inside their own sidebar and top bar.
 */
class RoleShell
{
    public static function layoutFor(?User $user): string
    {
        return match ((int) ($user?->usergroup_id)) {
            1 => 'layouts.superadmin.layout',
            5 => 'layouts.teacher.layout',
            6 => 'layouts.student.layout',
            7 => 'layouts.parent.layout',
            8 => 'layouts.library.layout',
            9 => 'layouts.alumni.layout',
            10 => 'layouts.reception.layout',
            11 => 'layouts.accountant.layout',
            12 => 'layouts.stock.layout',
            default => 'layouts.admin.layout', // 3 school admin, 4 sub-admin
        };
    }

    /** Plain-language role name for the account pages. */
    public static function roleLabel(?User $user): string
    {
        return match ((int) ($user?->usergroup_id)) {
            1 => 'Platform admin',
            3 => 'School admin',
            4 => 'School sub-admin',
            5 => 'Teacher',
            6 => 'Student',
            7 => 'Parent',
            8 => 'Librarian',
            9 => 'Alumni',
            10 => 'Receptionist',
            11 => 'Bursar',
            12 => 'Stock keeper',
            default => 'Staff',
        };
    }
}
