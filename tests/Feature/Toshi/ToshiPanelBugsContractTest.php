<?php

namespace Tests\Feature\Toshi;

use App\Livewire\AgentToshi;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Soft-launch Toshi panel bugs: expand persistence sync, unique input id,
 * composer stays typeable (with reason) while confirm chips are primary.
 */
class ToshiPanelBugsContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    private function admin(): User
    {
        $school = School::create([
            'name' => 'Panel Bugs School',
            'email' => 'panel-bugs@klassapp.test',
            'phone' => '0700000011',
            'status' => 1,
            'slug' => 'panel-bugs-school',
            'toshi_enabled' => 1,
        ]);

        return User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'email' => 'panel.bugs.admin@klassapp.test',
            'status' => 'active',
        ]);
    }

    /** Count real DOM nodes — Livewire wire:snapshot also embeds the markup as JSON. */
    private function countIds(string $html, string $id): int
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);

        return (new \DOMXPath($dom))->query('//*[@id="'.$id.'"]')->length;
    }

    public function test_docked_composer_has_exactly_one_toshi_input_panel_id(): void
    {
        $html = Livewire::actingAs($this->admin())
            ->test(AgentToshi::class)
            ->set('visible', true)
            ->set('maximized', false)
            ->html();

        $this->assertSame(1, $this->countIds($html, 'toshi-input-panel'));
        $this->assertSame(0, $this->countIds($html, 'toshi-input-modal'));
    }

    public function test_maximized_composer_has_exactly_one_toshi_input_modal_id(): void
    {
        $html = Livewire::actingAs($this->admin())
            ->test(AgentToshi::class)
            ->set('visible', false)
            ->set('maximized', true)
            ->html();

        $this->assertSame(0, $this->countIds($html, 'toshi-input-panel'));
        $this->assertSame(1, $this->countIds($html, 'toshi-input-modal'));
    }

    public function test_awaiting_confirm_keeps_composer_typeable_and_shows_reason(): void
    {
        $html = Livewire::actingAs($this->admin())
            ->test(AgentToshi::class)
            ->set('visible', true)
            ->set('awaitingConfirm', true)
            ->html();

        $this->assertStringContainsString('data-testid="toshi-composer-reason"', $html);
        $this->assertStringContainsString('id="toshi-input-panel"', $html);
        $this->assertStringNotContainsString('readonly', $html);
        $this->assertStringContainsString('Type yes or no', $html);
    }

    public function test_embed_dispatches_collapsed_changed_and_prepaint_reads_expanded(): void
    {
        $embed = file_get_contents(resource_path('views/layouts/partials/toshi-embed.blade.php'));
        $prepaint = file_get_contents(resource_path('views/layouts/partials/toshi-prepaint.blade.php'));
        $blade = file_get_contents(resource_path('views/livewire/agent-toshi.blade.php'));

        $this->assertStringContainsString('toshi-collapsed-changed', $embed);
        $this->assertStringContainsString("toshi_split_collapsed') !== '0'", $prepaint);
        $this->assertStringContainsString('toshi-collapsed-changed', $blade);
        $this->assertStringContainsString("\$wire.set('visible', true)", $blade);
    }

    public function test_published_css_allows_typing_while_awaiting_confirm(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertStringContainsString('.toshi-composer-reason', $css);
        $this->assertStringNotContainsString(
            '.toshi-composer--awaiting-confirm .toshi-composer-input {
    pointer-events: none;
}',
            $css
        );
    }
}
