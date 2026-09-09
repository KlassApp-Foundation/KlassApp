<?php

namespace App\Support;

use App\Models\User;

/**
 * Role-aware account links for the shared profile dropdown.
 *
 * Only returns URLs for routes that actually exist on that portal
 * (verified against routes/* — do not invent /student/editprofile etc.).
 */
class PortalProfileLinks
{
    /**
     * @return array{
     *     change_password: ?string,
     *     change_avatar: ?string,
     *     edit_profile: ?string,
     *     settings: ?string
     * }
     */
    public static function forUser(User $user): array
    {
        $ug = (int) $user->usergroup_id;

        return match ($ug) {
            1 => [ // SiteAdmin / Superadmin
                'change_password' => '/superadmin/changepassword',
                'change_avatar' => '/superadmin/changeavatar',
                'edit_profile' => null,
                'settings' => '/superadmin/settings',
            ],
            3 => [ // SchoolAdmin
                'change_password' => '/admin/changepassword',
                'change_avatar' => '/admin/changeavatar',
                'edit_profile' => '/admin/editprofile',
                'settings' => '/admin/settings',
            ],
            5 => [ // Teacher
                'change_password' => '/teacher/changepassword',
                'change_avatar' => '/teacher/changeavatar',
                'edit_profile' => null,
                'settings' => null,
            ],
            6 => [ // Student — password only
                'change_password' => '/student/changepassword',
                'change_avatar' => null,
                'edit_profile' => null,
                'settings' => null,
            ],
            10 => [ // Receptionist
                'change_password' => '/receptionist/changepassword',
                'change_avatar' => '/receptionist/changeavatar',
                'edit_profile' => null,
                'settings' => null,
            ],
            11 => [ // Accountant
                'change_password' => '/accountant/changepassword',
                'change_avatar' => '/accountant/changeavatar',
                'edit_profile' => null,
                'settings' => null,
            ],
            // Parent (7), Librarian (8), Alumni (9), Subadmin (4), Stock (12): no account mutators
            default => [
                'change_password' => null,
                'change_avatar' => null,
                'edit_profile' => null,
                'settings' => null,
            ],
        };
    }
}
