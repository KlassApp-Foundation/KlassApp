<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace Tests\Feature\Navigation;

use App\Helpers\AuthRedirectHelper;
use Tests\TestCase;

/**
 * Soft-launch 1f: Admissions in admin sidebar, Stock hidden, alumni login redirect.
 */
class SoftlaunchNavAdmissionsStockAlumniContractTest extends TestCase
{
    public function test_admin_sidebar_includes_admissions(): void
    {
        // PR1 regrouped the sidebar; Admissions now sits in People. The contract
        // (Admissions reachable from the admin sidebar) is unchanged.
        $items = collect(config('navigation.roles.admin.groups'))
            ->flatMap(fn (array $group) => $group['items'] ?? [])
            ->values();
        $admissions = $items->firstWhere('label', 'Admissions');
        $this->assertIsArray($admissions);
        $this->assertSame('admin/admissions', $admissions['url']);
    }

    public function test_stock_sidebar_has_no_visible_items(): void
    {
        $items = config('navigation.roles.stock.items');
        $this->assertIsArray($items);
        $this->assertSame([], array_values($items));
        $nav = file_get_contents(config_path('navigation.php'));
        $this->assertStringNotContainsString("'url' => 'stock/dashboard'", $nav);
    }

    public function test_stock_keeper_removed_from_staff_designations(): void
    {
        $source = file_get_contents(app_path('Helpers/SiteHelper.php'));
        $this->assertStringNotContainsString('Stock Keeper', $source);
    }

    public function test_alumni_redirects_to_alumni_dashboard(): void
    {
        $this->assertSame('/alumni/dashboard', AuthRedirectHelper::dashboardPathForUsergroup(9));
    }

    public function test_stock_keeper_does_not_redirect_into_empty_stock_portal(): void
    {
        $this->assertSame('/admin/dashboard', AuthRedirectHelper::dashboardPathForUsergroup(12));
        $this->assertNotSame('/stock/dashboard', AuthRedirectHelper::dashboardPathForUsergroup(12));
    }
}
