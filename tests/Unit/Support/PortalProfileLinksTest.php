<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\PortalProfileLinks;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortalProfileLinksTest extends TestCase
{
    #[Test]
    #[DataProvider('roleLinkMatrix')]
    public function maps_usergroup_to_existing_portal_routes(
        int $usergroupId,
        ?string $changePassword,
        ?string $changeAvatar,
        ?string $editProfile,
        ?string $settings,
    ): void {
        $user = new User(['usergroup_id' => $usergroupId]);

        $this->assertSame([
            'change_password' => $changePassword,
            'change_avatar' => $changeAvatar,
            'edit_profile' => $editProfile,
            'settings' => $settings,
        ], PortalProfileLinks::forUser($user));
    }

    public static function roleLinkMatrix(): array
    {
        return [
            'superadmin' => [1, '/superadmin/changepassword', '/superadmin/changeavatar', null, '/superadmin/settings'],
            'school_admin' => [3, '/admin/changepassword', '/admin/changeavatar', '/admin/editprofile', '/admin/settings'],
            'subadmin' => [4, null, null, null, null],
            'teacher' => [5, '/teacher/changepassword', '/teacher/changeavatar', null, null],
            'student' => [6, '/student/changepassword', null, null, null],
            'parent' => [7, null, null, null, null],
            'librarian' => [8, null, null, null, null],
            'alumni' => [9, null, null, null, null],
            'receptionist' => [10, '/receptionist/changepassword', '/receptionist/changeavatar', null, null],
            'accountant' => [11, '/accountant/changepassword', '/accountant/changeavatar', null, null],
            'stock' => [12, null, null, null, null],
        ];
    }
}
