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
    public function student_dropdown_links_to_student_password_only(): void
    {
        $this->actingAsPortalUser(6, 6001, 'Student Nav', 'student-nav@example.test');

        $html = view('layouts.partials.profile-dropdown')->render();

        $this->assertStringContainsString('/student/changepassword', $html);
        $this->assertStringContainsString('dusk="password-link"', $html);
        $this->assertStringNotContainsString('/admin/changepassword', $html);
        $this->assertStringNotContainsString('/admin/editprofile', $html);
        $this->assertStringNotContainsString('/admin/settings', $html);
        $this->assertStringNotContainsString('/admin/changeavatar', $html);
        $this->assertStringNotContainsString('Edit Profile', $html);
        $this->assertStringNotContainsString('>Settings<', $html);
        $this->assertStringNotContainsString('dusk="edit-profile-link"', $html);
        $this->assertStringNotContainsString('dusk="settings-link"', $html);
    }

    #[Test]
    public function parent_dropdown_hides_account_mutator_links(): void
    {
        $this->actingAsPortalUser(7, 7001, 'Parent Nav', 'parent-nav@example.test');

        $html = view('layouts.partials.profile-dropdown')->render();

        $this->assertStringNotContainsString('Change Password', $html);
        $this->assertStringNotContainsString('Edit Profile', $html);
        $this->assertStringNotContainsString('/admin/', $html);
        $this->assertStringContainsString('Logout', $html);
    }

    #[Test]
    public function teacher_dropdown_uses_teacher_password_and_avatar(): void
    {
        $this->actingAsPortalUser(5, 5001, 'Teacher Nav', 'teacher-nav@example.test');

        $html = view('layouts.partials.profile-dropdown')->render();

        $this->assertStringContainsString('/teacher/changepassword', $html);
        $this->assertStringContainsString('/teacher/changeavatar', $html);
        $this->assertStringNotContainsString('/admin/changepassword', $html);
        $this->assertStringNotContainsString('Edit Profile', $html);
        $this->assertStringNotContainsString('dusk="settings-link"', $html);
    }

    #[Test]
    public function school_admin_dropdown_keeps_admin_account_links(): void
    {
        $this->actingAsPortalUser(3, 3001, 'Admin Nav', 'admin-nav@example.test');

        $html = view('layouts.partials.profile-dropdown')->render();

        $this->assertStringContainsString('/admin/changepassword', $html);
        $this->assertStringContainsString('/admin/editprofile', $html);
        $this->assertStringContainsString('/admin/settings', $html);
        $this->assertStringContainsString('/admin/changeavatar', $html);
    }

    #[Test]
    public function parent_layout_nav_has_logout_only_no_admin_account_links(): void
    {
        $source = file_get_contents(resource_path('views/layouts/parent/navigation.blade.php'));

        $this->assertNotFalse($source);
        $this->assertStringContainsString('route(\'logout\')', $source);
        $this->assertStringNotContainsString('profile-dropdown', $source);
        $this->assertStringNotContainsString('/admin/changepassword', $source);
        $this->assertStringNotContainsString('/admin/editprofile', $source);
    }
}
