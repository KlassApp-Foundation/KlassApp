<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileDropdownRoleAwareTest extends TestCase
{
    private function actingAsPortalUser(int $usergroupId, int $id, string $name, string $email): void
    {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'usergroup_id' => $usergroupId,
        ]);
        $user->id = $id;
        $user->setRelation('userprofile', null);
        // setUser avoids Login event / queued LogSuccessfulLogin (no jobs table in unit DB)
        Auth::guard('web')->setUser($user);
    }

    #[Test]
    public function student_dropdown_has_the_shared_profile_and_password_pages_and_no_admin_links(): void
    {
        $this->actingAsPortalUser(6, 6001, 'Student Nav', 'student-nav@example.test');

        $html = view('layouts.partials.profile-dropdown')->render();

        $this->assertStringContainsString('/account/profile', $html);
        $this->assertStringContainsString('/account/password', $html);
        $this->assertStringContainsString('dusk="password-link"', $html);
        $this->assertStringNotContainsString('/admin/changepassword', $html);
        $this->assertStringNotContainsString('/admin/editprofile', $html);
        $this->assertStringNotContainsString('/admin/settings', $html);
        $this->assertStringNotContainsString('/admin/changeavatar', $html);
        $this->assertStringNotContainsString('Edit profile', $html);
        $this->assertStringNotContainsString('>Settings<', $html);
        $this->assertStringNotContainsString('dusk="edit-profile-link"', $html);
        $this->assertStringNotContainsString('dusk="settings-link"', $html);
    }

    #[Test]
    public function parent_dropdown_has_profile_and_password_and_no_admin_links(): void
    {
        $this->actingAsPortalUser(7, 7001, 'Parent Nav', 'parent-nav@example.test');

        $html = view('layouts.partials.profile-dropdown')->render();

        $this->assertStringContainsString('/account/profile', $html);
        $this->assertStringContainsString('Change password', $html);
        $this->assertStringNotContainsString('Edit profile', $html);
        $this->assertStringNotContainsString('/admin/', $html);
        $this->assertStringContainsString('Log out', $html);
    }

    #[Test]
    public function teacher_dropdown_uses_the_shared_pages_and_the_teacher_avatar(): void
    {
        $this->actingAsPortalUser(5, 5001, 'Teacher Nav', 'teacher-nav@example.test');

        $html = view('layouts.partials.profile-dropdown')->render();

        $this->assertStringContainsString('/account/profile', $html);
        $this->assertStringContainsString('/account/password', $html);
        $this->assertStringContainsString('/teacher/changeavatar', $html);
        $this->assertStringNotContainsString('/admin/changepassword', $html);
        $this->assertStringNotContainsString('Edit profile', $html);
        $this->assertStringNotContainsString('dusk="settings-link"', $html);
    }

    #[Test]
    public function school_admin_dropdown_keeps_the_admin_extras(): void
    {
        $this->actingAsPortalUser(3, 3001, 'Admin Nav', 'admin-nav@example.test');

        $html = view('layouts.partials.profile-dropdown')->render();

        $this->assertStringContainsString('/account/profile', $html);
        $this->assertStringContainsString('/account/password', $html);
        $this->assertStringContainsString('/admin/settings', $html);
        $this->assertStringContainsString('/admin/changeavatar', $html);
    }

    #[Test]
    public function parent_layout_uses_the_shared_shell_with_chrome_in_the_sidebar(): void
    {
        // Every role shell passes chromeInSidebar, so the bell and the account menu live in the
        // sidebar footer (phones: the avatar in the top bar), exactly like the school admin's.
        // The parent no longer has a separate Logout button: Log out is in the account menu.
        $layout = file_get_contents(resource_path('views/layouts/parent/layout.blade.php'));
        $this->assertNotFalse($layout);
        $this->assertStringContainsString('layouts.partials.navigation', $layout);
        $this->assertStringContainsString("'chromeInSidebar' => true", $layout);
        $this->assertStringNotContainsString("'showLogout' => true", $layout);
        $this->assertStringContainsString("'brandRoute' => 'parent.dashboard'", $layout);
        $this->assertStringNotContainsString('/admin/changepassword', $layout);
        $this->assertStringNotContainsString('/admin/editprofile', $layout);

        $sidebar = file_get_contents(resource_path('views/layouts/parent/sidebar.blade.php'));
        $this->assertNotFalse($sidebar);
        $this->assertStringContainsString("'role' => 'parent'", $sidebar);
        $this->assertStringContainsString('layouts.partials.role-sidebar', $sidebar);
        $this->assertStringNotContainsString('/admin/changepassword', $sidebar);
        $this->assertStringNotContainsString('/admin/editprofile', $sidebar);
    }
}
