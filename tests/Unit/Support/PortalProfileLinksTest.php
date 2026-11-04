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
    public function every_role_shares_the_profile_and_password_pages_and_keeps_its_own_extras(
        int $usergroupId,
        ?string $changeAvatar,
        ?string $editProfile,
        ?string $settings,
    ): void {
        $user = new User(['usergroup_id' => $usergroupId]);

        $this->assertSame([
            'profile' => '/account/profile',
            'change_password' => '/account/password',
            'change_avatar' => $changeAvatar,
            'edit_profile' => $editProfile,
            'settings' => $settings,
        ], PortalProfileLinks::forUser($user));
    }

    public static function roleLinkMatrix(): array
    {
        return [
            'superadmin' => [1, '/superadmin/changeavatar', null, '/superadmin/settings'],
            'school_admin' => [3, '/admin/changeavatar', '/admin/editprofile', '/admin/settings'],
            'subadmin' => [4, null, null, null],
            'teacher' => [5, '/teacher/changeavatar', null, null],
            'student' => [6, null, null, null],
            'parent' => [7, null, null, null],
            'librarian' => [8, null, null, null],
            'alumni' => [9, null, null, null],
            'receptionist' => [10, '/receptionist/changeavatar', null, null],
            'accountant' => [11, '/accountant/changeavatar', null, null],
            'stock' => [12, null, null, null],
        ];
    }
}
