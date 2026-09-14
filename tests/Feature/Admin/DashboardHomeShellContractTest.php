<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class DashboardHomeShellContractTest extends TestCase
{
    public function test_home_shell_css_defines_kit_layout_and_preserves_live_badge(): void
    {
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));

        $this->assertStringContainsString('.dashboard-home-head', $css);
        $this->assertStringContainsString('.dashboard-topfold--kit', $css);
        $this->assertStringContainsString('.dashboard-connected-tools', $css);
        $this->assertStringContainsString('.dashboard-live-badge', $css);
        $this->assertStringContainsString('.dashboard-live-dot', $css);

        // Pulse / reduced-motion canaries must remain (do not regress #555 / #558 / #560).
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertMatchesRegularExpression(
            '/\.dashboard-shell--admin\s+\.dashboard-live-badge::after\s*\{[^}]*content:\s*none/s',
            $css
        );
    }

    public function test_pulse_toshi_css_still_greens_kpi_values_and_blurs_ledger_thead(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));

        $this->assertStringContainsString('.ds-kpi-card .ds-kpi-value', $css);
        $this->assertStringContainsString('color: #22C55E', $css);
        $this->assertStringContainsString('.ds-table-ledger thead', $css);
        $this->assertStringContainsString('backdrop-filter: blur(12px)', $css);
    }
}
