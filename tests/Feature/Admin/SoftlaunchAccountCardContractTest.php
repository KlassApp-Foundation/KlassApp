<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Soft-launch task 4 / Part A: account card in sidebar footer.
 * Spec: design/system/guidelines/handoff-2026-09-30-profiles.md § Part A.
 */
class SoftlaunchAccountCardContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_account_card_uses_firstname_lastname_not_login_handle(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 3,
            'name' => 'login.handle',
            'email' => 'ada@test.sch.ug',
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $user->school_id,
            'usergroup_id' => 3,
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
        ]);

        Auth::guard('web')->setUser($user->fresh(['userprofile']));

        $html = view('layouts.partials.profile-dropdown')->render();

        // Userprofile mutators store names uppercased; card must use firstname+lastname, not login handle.
        $this->assertStringContainsString('ADA LOVELACE', $html);
        $this->assertStringNotContainsString('login.handle', $html);
        $this->assertStringContainsString('aria-haspopup="menu"', $html);
        $this->assertStringContainsString('role="menu"', $html);
        $this->assertStringContainsString('Change password', $html);
        $this->assertStringContainsString('Edit profile', $html);
        $this->assertStringContainsString('Settings', $html);
        $this->assertStringContainsString('Log out', $html);
        $this->assertStringNotContainsString('Change Password', $html);
        $this->assertStringNotContainsString('onclick=', $html);
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('account-card__trigger', $html);
        $this->assertStringContainsString('min-height: 56px', file_get_contents(public_path('css/dashboard-refresh.css')));
        $this->assertStringContainsString('min-height: 44px', file_get_contents(public_path('css/dashboard-refresh.css')));
        $this->assertStringContainsString('direction: ltr', file_get_contents(public_path('css/dashboard-refresh.css')));
        $this->assertStringContainsString('#B91C1C', file_get_contents(public_path('css/dashboard-refresh.css')));
    }

    public function test_account_card_falls_back_to_users_name_without_profile_names(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 3,
            'name' => 'only-handle',
            'email' => 'handle@test.sch.ug',
            'status' => 'active',
        ]);
        $user->setRelation('userprofile', null);
        Auth::guard('web')->setUser($user);

        $html = view('layouts.partials.profile-dropdown')->render();
        $this->assertStringContainsString('only-handle', $html);
    }

    public function test_menu_order_icons_and_popover_geometry(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 3,
            'name' => 'geom.admin',
            'email' => 'geom@test.sch.ug',
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $user->school_id,
            'usergroup_id' => 3,
            'firstname' => 'Geometry',
            'lastname' => 'Admin',
        ]);
        Auth::guard('web')->setUser($user->fresh(['userprofile']));

        $html = view('layouts.partials.profile-dropdown')->render();

        // PR7 content order: identity row, Edit profile, Change password, Settings, separator, Log out.
        $edit = strpos($html, 'Edit profile');
        $pw = strpos($html, 'Change password');
        $settings = strpos($html, 'Settings');
        $logout = strpos($html, 'Log out');
        $this->assertNotFalse($edit);
        $this->assertNotFalse($pw);
        $this->assertNotFalse($settings);
        $this->assertNotFalse($logout);
        $this->assertTrue($edit < $pw && $pw < $settings && $settings < $logout, 'menu order must be Edit profile, Change password, Settings, Log out');

        // 18px icons on the menu rows.
        $this->assertGreaterThanOrEqual(3, substr_count($html, 'width="18" height="18"'));

        $css = file_get_contents(public_path('css/dashboard-refresh.css'));
        // Popover: 8px above the card, 224px wide via 248px sidebar minus 12px insets.
        $this->assertStringContainsString('bottom: calc(100% + 8px)', $css);
        $this->assertStringContainsString('width: 248px', $css);
        $this->assertStringContainsString('#app-sidebar-wrap', $css);
        $this->assertStringContainsString('account-card--topbar', $css);
        $this->assertStringContainsString('width: 18px', $css);
    }

    public function test_topbar_variant_for_mobile_hangs_from_the_top_bar(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 3,
            'name' => 'topbar.admin',
            'email' => 'topbar@test.sch.ug',
            'status' => 'active',
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $user->school_id,
            'usergroup_id' => 3,
            'firstname' => 'Topbar',
            'lastname' => 'Admin',
        ]);
        Auth::guard('web')->setUser($user->fresh(['userprofile']));

        $html = view('layouts.partials.profile-dropdown', ['variant' => 'topbar'])->render();

        $this->assertStringContainsString('account-card--topbar', $html);
        $this->assertStringContainsString('account-card__menu', $html);
        $this->assertStringContainsString('aria-haspopup="menu"', $html);
    }

    public function test_js_wires_keyboard_and_aria_for_account_card(): void
    {
        $js = file_get_contents(public_path('js/custom.js'));
        $this->assertStringContainsString('aria-expanded', $js);
        $this->assertStringContainsString('ArrowDown', $js);
        $this->assertStringContainsString('ArrowUp', $js);
        $this->assertStringContainsString('Escape', $js);
        $this->assertStringContainsString('pointerdown', $js);
        $this->assertStringContainsString('data-account-trigger', $js);
    }
}
