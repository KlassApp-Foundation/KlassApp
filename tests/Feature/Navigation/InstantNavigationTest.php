<?php

namespace Tests\Feature\Navigation;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Instant navigation (approved 2026-11-22, R2): a sidebar click swaps the page
 * body with wire:navigate, the progress bar is KlassApp green, and only the
 * Toshi panel and the command palette are persisted across swaps.
 *
 * Teacher marks entry, Marks Release, print/PDF pages and the onboarding wizard
 * stay full page loads on purpose.
 */
class InstantNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function renderSidebar(string $role, string $path): string
    {
        app()->instance('request', Request::create($path));

        return view('layouts.partials.sidebar-menu', ['role' => $role])->render();
    }

    public function test_sidebar_links_navigate_without_a_full_reload(): void
    {
        $html = $this->renderSidebar('admin', '/admin/dashboard');

        $this->assertStringContainsString('wire:navigate', $html, 'the sidebar asks Livewire to swap the page');
    }

    public function test_marks_release_stays_a_full_page_load(): void
    {
        $html = $this->renderSidebar('admin', '/admin/dashboard');

        $this->assertDoesNotMatchRegularExpression(
            '/<a[^>]*href="[^"]*\/admin\/marks\/releases[^"]*"[^>]*wire:navigate/',
            $html,
            'Marks Release is a one-way action and must not be a swap'
        );
    }

    public function test_the_onboarding_wizard_stays_a_full_page_load(): void
    {
        $html = $this->renderSidebar('admin', '/admin/dashboard');

        $this->assertDoesNotMatchRegularExpression(
            '/<a[^>]*href="[^"]*\/admin\/onboarding[^"]*"[^>]*wire:navigate/',
            $html,
            'the onboarding wizard owns form state a swap would lose'
        );
    }

    public function test_the_progress_bar_is_klassapp_green(): void
    {
        $this->assertTrue((bool) config('livewire.navigate.show_progress_bar'), 'the bar stays on');
        $this->assertSame('#15803D', config('livewire.navigate.progress_bar_color'), 'the brand green, not the Livewire default');
    }

    public function test_only_toshi_and_the_palette_are_persisted(): void
    {
        $this->assertSame(
            ['toshi-panel', 'command-palette'],
            $this->persistedKeys(),
            'the sidebar must not be persisted: it would keep a stale active item'
        );
    }

    public function test_the_shell_renders_both_persisted_blocks_once(): void
    {
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'school admin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        $school = School::create([
            'name' => 'Nav School',
            'email' => 'nav-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'nav-'.uniqid(),
            'status' => 1,
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
        ]);
        $admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $school->id,
            'email' => 'nav-admin-'.uniqid().'@t.sch.ug',
            'status' => 'active',
        ]);

        $html = $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="toshi-panel"', $html, 'the Toshi panel is on the shell');
        $this->assertStringContainsString('id="ds-command-palette"', $html, 'the palette is on the shell');
        $this->assertStringContainsString('wire:navigate', $html, 'and the sidebar navigates without a reload');
    }

    /**
     * @return list<string>
     */
    private function persistedKeys(): array
    {
        $source = file_get_contents(resource_path('views/layouts/app.blade.php'));
        preg_match_all("/@persist\('([^']+)'\)/", $source, $matches);

        return $matches[1];
    }
}
