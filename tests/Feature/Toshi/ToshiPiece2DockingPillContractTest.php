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
        // Drawer breakpoint: 1279px since 6d3142b1 (2026-09-21) unified the
        // sub-1280 drawer — the old 640px literal was stale (pre-existing A5).
        $this->assertStringContainsString('@media (max-width: 1279px)', $css);
        // Split layout (2026-09-27): the dock width is var(--toshi-w) with a
        // 380px fallback — the literal default. Older versions hardcoded 380px.
        $this->assertStringContainsString('var(--toshi-w, 380px)', $css);
        $this->assertStringContainsString('body.toshi-collapsed [data-toshi-root]', $css);
        // Sibling Livewire root docks beside #app (not below fold under overflow:hidden).
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 1280px\)\s*\{[^}]*body\s*\{[^}]*flex-direction:\s*row/s',
            $css
        );
        // Sibling arrangement: the toggle wrapper comment (stale needle
        // "Toshi mounts as a sibling of #app" was removed with the #792
        // reorganization — pre-existing A5). The flow-sibling contract is now
        // expressed by the wrapper rules below.
        $this->assertStringContainsString('.toshi-toggle-wrapper,', $css);
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
        // The toggle markup moved into the shared toshi-embed partial (PR #724,
        // 2026-09-21) — both shells include it. This test was asserting against
        // app.blade.php directly and went stale when the include landed (A5).
        $app = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString("layouts.partials.toshi-embed", $app);
        $embed = file_get_contents(resource_path('views/layouts/partials/toshi-embed.blade.php'));
        $this->assertStringContainsString('data-testid="toshi-toggle"', $embed);
        $this->assertStringContainsString('data-testid="toshi-toggle-wrapper"', $embed);
    }

    public function test_agent_toshi_pill_exposes_testid(): void
    {
        $blade = file_get_contents(resource_path('views/livewire/agent-toshi.blade.php'));
        $this->assertStringContainsString('data-testid="toshi-pill"', $blade);
        $this->assertStringContainsString('toshi-modal-overlay--open', $blade);
    }
}
