<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;

/**
 * The legacy admin dashboard (Chart.js doughnuts) is gone. Gender is the v2
 * bar card, which does not put a studentCount subscript inside a component tag.
 */
class DashboardGenderChartTest extends TestCase
{
    public function test_legacy_dashboard_is_gone_and_v2_shows_gender(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/admin/dashboard/dashboard.blade.php'));

        $blade = file_get_contents(resource_path('views/admin/dashboard/dashboard_v2.blade.php'));
        $this->assertStringContainsString('data-testid="dashboard-v2-chart-gender"', $blade);
        $this->assertStringContainsString('Students by gender', $blade);
        $this->assertStringNotContainsString("['studentCount']", $blade);
    }
}
