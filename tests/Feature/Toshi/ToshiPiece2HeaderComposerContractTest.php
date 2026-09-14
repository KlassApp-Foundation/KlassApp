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
 * Piece 2 PR1 — header/composer chrome + Pulse freeze canary on published CSS.
 */
class ToshiPiece2HeaderComposerContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_published_toshi_ui_css_keeps_pulse_ledger_blur_canary(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString('FROZEN — Pulse design system', $css);
        $this->assertStringContainsString('.ds-table-ledger thead', $css);
        $this->assertMatchesRegularExpression(
            '/\.ds-table-ledger thead\s*\{[^}]*backdrop-filter:\s*blur\(12px\)/s',
            $css
        );
        $this->assertStringContainsString('.ds-kpi-card', $css);
    }

    public function test_published_toshi_ui_css_includes_piece2_header_composer_tokens(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString('PIECE 2 — Toshi panel chrome', $css);
        $this->assertStringContainsString('--toshi-clay: #c96442', $css);
        $this->assertStringContainsString('.toshi-confirm-chips', $css);
        $this->assertStringContainsString('.toshi-composer--awaiting-confirm', $css);
        $this->assertStringContainsString('border-radius: 16px', $css);
    }

    public function test_source_and_published_toshi_ui_css_match(): void
    {
        $source = file_get_contents(base_path('packages/toshi-ui/resources/css/toshi-ui.css'));
        $published = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertSame($source, $published, 'Run: php artisan vendor:publish --tag=toshi-ui-css --force');
    }

    public function test_awaiting_confirm_renders_chip_buttons_not_free_text_primary(): void
    {
        $school = School::create([
            'name' => 'Piece2 Chip School',
            'email' => 'piece2-chip@klassapp.test',
            'phone' => '0700000099',
            'status' => 1,
            'slug' => 'piece2-chip-school',
            'toshi_enabled' => 1,
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'email' => 'piece2.chip.admin@klassapp.test',
            'status' => 'active',
        ]);

        $html = Livewire::actingAs($admin)
            ->test(AgentToshi::class)
            ->set('visible', true)
            ->set('awaitingConfirm', true)
            ->html();

        $this->assertStringContainsString('data-testid="toshi-confirm-chips"', $html);
        $this->assertStringContainsString('data-testid="toshi-confirm-yes"', $html);
        $this->assertStringContainsString('data-testid="toshi-confirm-no"', $html);
        $this->assertStringContainsString('wire:click="confirmYes"', $html);
        $this->assertStringContainsString('toshi-composer--awaiting-confirm', $html);
        $this->assertStringContainsString('Use Yes / No above', $html);
    }
}
