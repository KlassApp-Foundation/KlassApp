<?php

namespace Tests\Feature\Toshi;

use App\Http\Middleware\MustBePrivilege;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ToshiEntryPillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->withoutMiddleware([MustBePrivilege::class]);
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    private function dashboard(string $mode, bool $enabled, string $path = '/admin/dashboard', int $usergroup = 3)
    {
        $school = School::create([
            'name' => 'Pill School '.Str::random(4),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => $enabled ? 1 : 0,
            'toshi_mode' => $mode,
        ]);
        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => $usergroup,
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => $usergroup,
            'firstname' => 'Pill',
            'lastname' => 'Admin',
        ]);

        return $this->actingAs($admin)->get($path);
    }

    public function test_toshi_pill_is_visible_from_1280_in_preview_with_meet_toshi(): void
    {
        $css = file_get_contents(base_path('packages/toshi-ui/resources/css/toshi-ui.css'));
        $response = $this->dashboard('preview', false)->assertOk();
        $response->assertSee('data-testid="toshi-entry-pill"', false);
        $response->assertSee('aria-label="Open Toshi"', false);
        $response->assertSee('Meet Toshi', false);
        $response->assertSee('id="toshi-toggle"', false);
        $this->assertStringContainsString('@media (min-width: 1280px)', $css);
        $this->assertStringContainsString('height: 44px', $css);
        $this->assertStringContainsString('border-radius: 999px', $css);
    }

    public function test_toshi_pill_onboarding_label_is_setup_guide(): void
    {
        config(['dashboard.v2_enabled' => true]);
        $html = $this->dashboard('onboarding', false)->assertOk()->assertSee('Setup guide', false)->getContent();
        $this->assertSame(1, preg_match('/data-setup-done="(\d+)" data-setup-total="(\d+)"/', $html, $count));
        $this->assertGreaterThan((int) $count[1], (int) $count[2]);
        $this->assertStringContainsString('>'.$count[1].'/'.$count[2].'<', $html);
        $this->assertSame(1, preg_match('/aria-valuemax="(\d+)" aria-valuenow="(\d+)"/', $html, $bar));
        $this->assertSame($count[2], $bar[1]);
        $this->assertSame($count[1], $bar[2]);
    }

    public function test_toshi_pill_assistant_label_is_talk(): void
    {
        $this->dashboard('assistant', true)->assertOk()->assertSee('>Talk<', false);
    }

    public function test_toshi_pill_k_is_brand_green_and_hidden_below_1280_and_while_open(): void
    {
        $css = file_get_contents(base_path('packages/toshi-ui/resources/css/toshi-ui.css'));
        $section = explode('TOSHI ENTRY PILL', $css, 2)[1] ?? '';
        $this->assertNotSame('', $section);
        $this->assertStringContainsString('color: #15803D', $section);
        $this->assertStringContainsString('toshi-entry-ring', $section);
        $this->assertStringContainsString('font-weight: 700', $section);
        $this->assertStringNotContainsString('font-size: 20px', $section);
        $this->assertStringContainsString('body:not(.toshi-collapsed) #toshi-entry-pill', $section);
        $this->assertStringContainsString('@media (max-width: 1279px)', $section);
        $this->assertStringContainsString('#toshi-entry-pill { display: none !important; }', $section);
        $this->assertStringNotContainsString('#c96442', $section);
        $this->assertStringContainsString('z-index: 40', $section);
    }

    public function test_toshi_pill_click_opens_the_panel(): void
    {
        $embed = file_get_contents(resource_path('views/layouts/partials/toshi-embed.blade.php'));
        $this->assertStringContainsString("document.getElementById('toshi-entry-pill')", $embed);
        $this->assertStringContainsString('setCollapsed(false)', $embed);
        $js = file_get_contents(public_path('js/toshi-entry-pill.js'));
        $this->assertStringContainsString('data-sticky-bar', $js);
        $this->assertStringContainsString('toshi_opened_', $js);
        $this->assertStringContainsString('tabIndex', $js);
        $this->assertStringContainsString('toshi-pill-on', $js);
        $this->assertStringContainsString("addEventListener('load', sync)", $js);
        $embed = file_get_contents(resource_path('views/layouts/partials/toshi-embed.blade.php'));
        $this->assertStringContainsString('window.toshiSyncLauncher', $embed);
        $spacing = file_get_contents(base_path('resources/assets/design-system/tokens/spacing.css'));
        $this->assertStringContainsString('--d-toshi-pill-zone: 76px', $spacing);
    }

    public function test_toshi_pill_does_not_dodge_rows_and_collapses_after_scroll(): void
    {
        $js = file_get_contents(public_path('js/toshi-entry-pill.js'));
        $this->assertStringNotContainsString("querySelectorAll('a, button, [data-sticky-bar]')", $js);
        $this->assertStringContainsString('is-compact', $js);
        $this->assertStringContainsString('COMPACT_AFTER = 80', $js);
        $css = file_get_contents(base_path('packages/toshi-ui/resources/css/toshi-ui.css'));
        $section = explode('TOSHI ENTRY PILL', $css, 2)[1] ?? '';
        $this->assertStringContainsString('right: calc(24px + env(safe-area-inset-right))', $section);
        $this->assertStringContainsString('#toshi-entry-pill.is-compact .toshi-entry-label', $section);
        $this->assertStringContainsString('width: 44px', $section);
    }

    public function test_toshi_pill_page_flag_is_on_for_the_dashboard_and_onboarding_only(): void
    {
        $this->dashboard('preview', false)->assertOk()->assertSee('data-pill-page="1"', false);
        $this->dashboard('preview', false, '/admin/onboarding/wizard')->assertOk()->assertSee('data-pill-page="1"', false);
        $list = $this->dashboard('preview', false, '/admin/students');
        $list->assertOk()->assertSee('data-toshi-mode="preview"', false)->assertSee('data-pill-page="0"', false);
        $list->assertDontSee('data-pill-page="1"', false);
        $js = file_get_contents(public_path('js/toshi-entry-pill.js'));
        $this->assertStringContainsString("wrap.dataset.pillPage !== '1'", $js);
    }

    public function test_toshi_pill_compacts_from_the_element_that_really_scrolls(): void
    {
        $js = file_get_contents(public_path('js/toshi-entry-pill.js'));
        $this->assertStringContainsString('IntersectionObserver', $js);
        $this->assertStringContainsString(".dashboard-content-area", $js);
        $this->assertStringNotContainsString("window.addEventListener('scroll'", $js);
        $this->assertStringNotContainsString('window.scrollY > 80', $js);
    }

    /** School admin (3) and head teacher (4) get the pill on their dashboard. */
    public function test_toshi_pill_is_rendered_on_the_dashboard_for_admin_and_head_teacher(): void
    {
        foreach ([3 => '/admin/dashboard', 4 => '/admin/dashboard'] as $usergroup => $path) {
            $html = $this->dashboard('preview', false, $path, $usergroup)->assertOk()->getContent();
            $this->assertStringContainsString('id="toshi-toggle"', $html, 'handle for usergroup '.$usergroup);
            $this->assertStringContainsString('data-testid="toshi-entry-pill"', $html, 'pill for usergroup '.$usergroup);
            $this->assertStringContainsString('data-pill-page="1"', $html, 'pill page for usergroup '.$usergroup);
        }
    }

    /** Teacher (5), parent (7), librarian (8) and bursar (11) keep the side handle only. */
    public function test_toshi_pill_is_not_rendered_on_the_dashboard_for_other_roles(): void
    {
        foreach ([5 => '/teacher/dashboard', 7 => '/parent/dashboard', 8 => '/library/dashboard', 11 => '/accountant/dashboard'] as $usergroup => $path) {
            $html = $this->dashboard('preview', false, $path, $usergroup)->assertOk()->getContent();
            $this->assertStringContainsString('id="toshi-toggle"', $html, 'handle for usergroup '.$usergroup);
            $this->assertStringNotContainsString('data-testid="toshi-entry-pill"', $html, 'pill for usergroup '.$usergroup);
            $this->assertStringContainsString('data-pill-page="0"', $html, 'pill page for usergroup '.$usergroup);
        }
    }
}
