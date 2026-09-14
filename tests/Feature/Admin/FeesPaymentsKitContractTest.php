<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class FeesPaymentsKitContractTest extends TestCase
{
    public function test_fees_payments_blade_matches_kit_composition(): void
    {
        $blade = file_get_contents(resource_path('views/admin/fees/payments.blade.php'));

        $this->assertStringContainsString('ds-page-head-title', $blade);
        $this->assertStringContainsString('Fee payments', $blade);
        $this->assertStringContainsString('fees-kpi-grid', $blade);
        $this->assertStringContainsString('x-ds-kpi-card', $blade);
        $this->assertStringContainsString('fees-record-form', $blade);
        $this->assertStringContainsString('ds-save-indicator', $blade);
        $this->assertStringContainsString('ds-save-indicator--saved', $blade);
        $this->assertStringContainsString('ds-save-indicator--saving', $blade);
        $this->assertStringContainsString('dt-name-link', $blade);
        $this->assertStringContainsString('Record payment', $blade);
        $this->assertStringContainsString("@push('scripts')", $blade);
    }

    public function test_reduced_motion_still_stops_save_indicator_d_pulse(): void
    {
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));

        $this->assertStringContainsString('animation: d-pulse', $css);
        $this->assertMatchesRegularExpression(
            '/\.ds-save-indicator--saving\s+\.ds-save-indicator__dot\s*\{[^}]*animation:\s*none/s',
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
