<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Support\AccountRoleFixture;
use Tests\TestCase;

/** The account menu is the same for every role: Profile, Change password, Log out. */
class AccountMenuConsistencyTest extends TestCase
{
    use AccountRoleFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildRoleFixture();
    }

    /** @return list<string> the menu item labels, in order */
    private function menuLabels(User $user): array
    {
        Auth::guard('web')->setUser($user);
        $html = view('layouts.partials.profile-dropdown')->render();

        preg_match_all('/role="menuitem"[^>]*>(.*?)<\/(?:a|button)>/s', $html, $m);

        return array_values(array_map(fn ($t) => trim(preg_replace('/\s+/', ' ', strip_tags($t))), $m[1]));
    }

    public function test_every_role_gets_profile_change_password_and_log_out_in_that_order(): void
    {
        foreach ($this->allRoleIds() as $id) {
            $labels = $this->menuLabels($this->userFor($id));
            $core = array_values(array_intersect($labels, ['Profile', 'Change password', 'Log out']));

            $this->assertSame(['Profile', 'Change password', 'Log out'], $core, self::ROLES[$id].' menu: '.json_encode($labels));
            $this->assertSame('Log out', end($labels), self::ROLES[$id].': Log out is last');
        }
    }

    public function test_settings_stay_with_admins_and_help_reaches_head_and_class_teachers(): void
    {
        $plain = ['Profile', 'Change password', 'Log out'];
        foreach ([6, 7, 8, 9, 10, 11, 12] as $id) {
            $this->assertSame($plain, $this->menuLabels($this->userFor($id)), self::ROLES[$id]);
        }

        $withHelp = ['Profile', 'Change password', 'Help', 'Log out'];
        foreach ([4, 5] as $id) {
            $this->assertSame($withHelp, $this->menuLabels($this->userFor($id)), self::ROLES[$id]);
        }

        $this->assertSame(['Profile', 'Change password', 'Settings', 'Help', 'Log out'], $this->menuLabels($this->userFor(3)));
        $this->assertSame(['Profile', 'Change password', 'Settings', 'Log out'], $this->menuLabels($this->userFor(1)));
    }

    public function test_help_deep_link_is_for_admin_head_teacher_and_class_teacher_only(): void
    {
        foreach ([3, 4, 5] as $id) {
            Auth::guard('web')->setUser($this->userFor($id));
            $html = view('layouts.partials.profile-dropdown')->render();

            $this->assertMatchesRegularExpression('#href="[^"]*/help/guide[^"]*" target="_blank"#', $html, self::ROLES[$id]);
            $this->assertStringContainsString('data-testid="account-menu-help"', $html, self::ROLES[$id]);
            $this->assertStringNotContainsString('https://klassapp.xyz/help', $html, self::ROLES[$id]);
        }

        foreach ([6, 7] as $id) {
            Auth::guard('web')->setUser($this->userFor($id));
            $html = view('layouts.partials.profile-dropdown')->render();

            $this->assertStringNotContainsString('/help/guide', $html, self::ROLES[$id]);
            $this->assertStringNotContainsString('data-testid="account-menu-help"', $html, self::ROLES[$id]);
            $this->assertNotContains('Help', $this->menuLabels($this->userFor($id)), self::ROLES[$id]);
        }
    }

    public function test_profile_and_password_point_at_the_same_pages_for_every_role(): void
    {
        foreach ($this->allRoleIds() as $id) {
            Auth::guard('web')->setUser($this->userFor($id));
            $html = view('layouts.partials.profile-dropdown')->render();

            $this->assertMatchesRegularExpression('#href="[^"]*/account/profile"#', $html, self::ROLES[$id]);
            $this->assertMatchesRegularExpression('#href="[^"]*/account/password"#', $html, self::ROLES[$id]);
            $this->assertStringContainsString('action="'.route('logout').'"', $html, self::ROLES[$id]);
        }
    }

    public function test_every_role_can_open_the_profile_and_password_pages_inside_its_own_shell(): void
    {
        foreach ($this->allRoleIds() as $id) {
            $this->flushSession(); // one browser session per role, like real life
            $user = $this->userFor($id);

            $this->actingAs($user)->get('/account/profile')->assertOk()
                ->assertSee('data-testid="account-profile"', false)
                ->assertSee($user->email);
            $this->actingAs($user)->get('/account/password')->assertOk()
                ->assertSee('data-testid="account-password"', false)
                ->assertSee('id="oldpassword"', false);
        }
    }

    public function test_the_public_demo_account_cannot_use_the_account_pages(): void
    {
        $demo = $this->userFor(3);
        $demo->forceFill(['is_public_demo' => true])->save();

        $this->actingAs($demo)->get('/account/password')->assertOk()->assertSee('data-testid="public-demo-soon"', false);
        $this->actingAs($demo)->post('/account/password', ['oldpassword' => 'old-pass-123', 'newpassword' => 'Brand-new-pass-77', 'confirmpassword' => 'Brand-new-pass-77'])
            ->assertOk()->assertSee('data-testid="public-demo-soon"', false);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('old-pass-123', $demo->fresh()->password));
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/account/profile')->assertRedirect('/login');
        $this->get('/account/password')->assertRedirect('/login');
    }
}
