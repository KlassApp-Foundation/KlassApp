<?php

namespace App\Support;

use App\Models\User;

/**
 * Account links for the shared account menu (layouts/partials/profile-dropdown).
 *
 * `profile` and `change_password` are the SAME role-agnostic pages for every role
 * (routes/account.php), so a teacher, a parent, a student, staff and a school admin all get
 * the same menu: Profile, Change password, Log out. The other keys are optional extras that
 * only some portals have, and only point at routes that exist on that portal (verified
 * against routes/*: do not invent /student/editprofile etc.).
 */
class PortalProfileLinks
{
    /** The pages every signed-in role shares. */
    public const PROFILE = '/account/profile';

    public const CHANGE_PASSWORD = '/account/password';

    /**
     * @return array{
     *     profile: ?string,
     *     change_password: ?string,
     *     change_avatar: ?string,
     *     edit_profile: ?string,
     *     settings: ?string
     * }
     */
    public static function forUser(User $user): array
    {
        // The public demo admin cannot change its password, avatar or profile.
        if ($user->isPublicDemo()) {
            return ['profile' => null, 'change_password' => null, 'change_avatar' => null, 'edit_profile' => null, 'settings' => '/admin/settings'];
        }

        $extras = match ((int) $user->usergroup_id) {
            1 => ['change_avatar' => '/superadmin/changeavatar', 'edit_profile' => null, 'settings' => '/superadmin/settings'],
            3 => ['change_avatar' => '/admin/changeavatar', 'edit_profile' => '/admin/editprofile', 'settings' => '/admin/settings'],
            5 => ['change_avatar' => '/teacher/changeavatar', 'edit_profile' => null, 'settings' => null],
            10 => ['change_avatar' => '/receptionist/changeavatar', 'edit_profile' => null, 'settings' => null],
            11 => ['change_avatar' => '/accountant/changeavatar', 'edit_profile' => null, 'settings' => null],
            // Student, parent, librarian, alumni, sub-admin, stock: no avatar or extra pages.
            default => ['change_avatar' => null, 'edit_profile' => null, 'settings' => null],
        };

        return ['profile' => self::PROFILE, 'change_password' => self::CHANGE_PASSWORD] + $extras;
    }
}
