<?php

namespace Tests\Feature\Toshi;

use Tests\TestCase;

/**
 * Piece 2 PR3 — docking / pill / fullscreen chrome + Pulse freeze canary.
 */
class ToshiPiece2DockingPillContractTest extends TestCase
{
    public function test_published_toshi_ui_css_keeps_pulse_ledger_blur_canary(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString('FROZEN — Pulse design system', $css);
        $this->assertMatchesRegularExpression(
            '/\.ds-table-ledger thead\s*\{[^}]*backdrop-filter:\s*blur\(12px\)/s',
            $css
        );
        $this->assertStringContainsString('.ds-kpi-card', $css);
        $this->assertStringContainsString('background: rgba(34,197,94,0.08)', $css);
    }

    public function test_source_and_published_toshi_ui_css_match(): void
    {
        $source = file_get_contents(base_path('packages/toshi-ui/resources/css/toshi-ui.css'));
        $published = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertSame($source, $published, 'Run: php artisan vendor:publish --tag=toshi-ui-css --force');
    }

    public function test_published_css_includes_piece2_docking_pill_tokens(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertStringContainsString('PIECE 2 — Docking / pill / fullscreen chrome', $css);
        $this->assertStringContainsString('@media (min-width: 1280px)', $css);
        $this->assertStringContainsString('@media (max-width: 640px)', $css);
        $this->assertStringContainsString('width: 380px', $css);
        $this->assertStringContainsString('body.toshi-collapsed [data-toshi-root]', $css);
        $this->assertStringContainsString('border-left: 1px solid #e8e6dc', $css);
        $this->assertStringContainsString('background: #c96442', $css);
        $this->assertStringContainsString('[data-toshi-root] .toshi-pill-badge', $css);
    }

    public function test_docking_chrome_before_frozen_pulse_is_not_pulse_green(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $dock = explode('FROZEN — Pulse design system', $css, 2)[0];
        $this->assertStringContainsString('PIECE 2 — Docking', $dock);
        $this->assertStringNotContainsString('#22C55E', $dock);
        $this->assertStringContainsString('#c96442', $dock);
    }

    public function test_layouts_expose_toshi_toggle_testid(): void
    {
        $app = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('data-testid="toshi-toggle"', $app);
        $this->assertStringContainsString('data-testid="toshi-toggle-wrapper"', $app);
    }

    public function test_agent_toshi_pill_exposes_testid(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/agent-toshi.blade.php'));
        $this->assertStringContainsString('data-testid="toshi-pill"', $blade);
    }
}
