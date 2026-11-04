<?php

namespace Tests\Feature\Account;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AccountRoleFixture;
use Tests\TestCase;

/**
 * The profile button and the notification bell sit in the same place for every role, the
 * school admin's way: bell + account card in the sidebar footer on desktop, the avatar in the
 * top bar on phones. The top bar itself no longer carries a second bell or account card.
 */
class RoleTopBarTest extends TestCase
{
    use AccountRoleFixture;
    use RefreshDatabase;

    /** usergroup => the notification mode that role's layout has always used */
    private const NOTIFY_MODE = [1 => 'superadmin', 3 => 'admin', 5 => 'teacher', 6 => 'student', 7 => 'parent', 8 => 'library', 9 => 'alumni', 10 => 'receptionist', 11 => 'accountant'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildRoleFixture();
    }

    private function page(int $usergroupId): string
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();

        return $this->actingAs($this->userFor($usergroupId))->get('/account/profile')->assertOk()->getContent();
    }

    private function topBar(string $html): string
    {
        $this->assertSame(1, preg_match('#<nav class="navbar.*?</nav>#s', $html, $m), 'the top bar renders');

        return $m[0];
    }

    public function test_teacher_parent_and_student_top_bars_render_the_profile_and_the_bell_like_the_admins(): void
    {
        foreach ([3, 5, 6, 7] as $id) {
            $html = $this->page($id);
            $bar = $this->topBar($html);

            // Profile: the avatar button in the top bar (phones), the account card in the sidebar footer (desktop).
            $this->assertStringContainsString('account-card--topbar', $bar, self::ROLES[$id].' top bar avatar');
            $this->assertStringContainsString('data-account-trigger', $bar, self::ROLES[$id]);
            $this->assertStringContainsString('data-testid="dashboard-sidebar-footer"', $html, self::ROLES[$id].' sidebar footer');

            // Bell: in the sidebar footer, with the role's own notification mode, and not duplicated in the top bar.
            $this->assertStringContainsString('<notification url=', $html, self::ROLES[$id].' bell');
            $this->assertStringContainsString('mode="'.self::NOTIFY_MODE[$id].'"', $html, self::ROLES[$id]);
            $this->assertStringNotContainsString('<notification', $bar, self::ROLES[$id].' top bar has no second bell');
        }
    }

    public function test_every_role_has_exactly_the_same_chrome_as_the_school_admin(): void
    {
        $count = fn (string $html, string $needle) => substr_count($html, $needle);
        $admin = $this->page(3);

        foreach (array_keys(self::NOTIFY_MODE) as $id) {
            $html = $this->page($id);

            foreach (['data-account-trigger', '<notification url=', 'data-testid="dashboard-sidebar-footer"', 'id="admin-sidebar"', 'id="res_sidebar"', 'id="mobile-menu-trigger"'] as $needle) {
                $this->assertSame($count($admin, $needle), $count($html, $needle), self::ROLES[$id]." renders {$needle} as often as the school admin");
            }
            $this->assertStringContainsString('class="ml-auto md:hidden flex items-center"', $this->topBar($html), self::ROLES[$id]);
        }
    }

    public function test_the_parent_top_bar_has_no_separate_logout_button_any_more(): void
    {
        $bar = $this->topBar($this->page(7));

        $this->assertStringNotContainsString('>Logout<', $bar);
        $this->assertStringContainsString('data-account-trigger', $bar);
    }
}
