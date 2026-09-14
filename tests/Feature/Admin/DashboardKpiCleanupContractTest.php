<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class DashboardKpiCleanupContractTest extends TestCase
{
    public function test_approvals_inbox_uses_ds_kpi_cards(): void
    {
        $blade = file_get_contents(resource_path('views/admin/approvals/inbox.blade.php'));

        $this->assertStringContainsString('approvals-kpi-grid', $blade);
        $this->assertStringContainsString('x-ds-kpi-card', $blade);
        $this->assertStringNotContainsString('dashboard-kpi-card', $blade);
    }

    public function test_superadmin_dashboard_uses_ds_kpi_value_hooks(): void
    {
        $blade = file_get_contents(resource_path('views/superadmin/dashboard.blade.php'));

        $this->assertStringContainsString('ds-kpi-card', $blade);
        $this->assertStringContainsString('ds-kpi-value', $blade);
        $this->assertStringContainsString('ds-kpi-label', $blade);
        $this->assertStringNotContainsString('dashboard-kpi-card', $blade);
        $this->assertStringNotContainsString('dashboard-kpi-value', $blade);
    }

    public function test_eot_performance_panel_is_not_misclassified_as_kpi_card(): void
    {
        $blade = file_get_contents(resource_path('views/admin/reports/_eot-kpi-card.blade.php'));

        $this->assertStringContainsString('dashboard-chart-card', $blade);
        $this->assertStringNotContainsString('dashboard-kpi-card', $blade);
    }

    public function test_no_blade_view_still_uses_dashboard_kpi_card(): void
    {
        $hits = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (str_contains($contents, 'dashboard-kpi-card')) {
                $hits[] = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        $this->assertSame([], $hits, 'Remaining dashboard-kpi-card usages: '.implode(', ', $hits));
    }

    public function test_pulse_toshi_css_still_greens_kpi_values(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));

        $this->assertStringContainsString('.ds-kpi-card .ds-kpi-value', $css);
        $this->assertStringContainsString('color: #22C55E', $css);
    }
}
