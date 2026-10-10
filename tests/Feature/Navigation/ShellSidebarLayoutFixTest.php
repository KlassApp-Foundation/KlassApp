<?php

namespace Tests\Feature\Navigation;

use Tests\TestCase;

/**
 * K55/K63 shell fix: compact sidebar rows render icon-LEFT (legacy admin.css
 * forced flex-direction: column) and groups show their items by default.
 */
class ShellSidebarLayoutFixTest extends TestCase
{
    public function test_sidebar_items_override_the_legacy_column_layout(): void
    {
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));

        $this->assertStringContainsString('#admin-sidebar.admin-sidebar li a', $css);
        $this->assertStringContainsString('flex-direction: row;', $css);

        $legacy = file_get_contents(public_path('css/admin.css'));
        $this->assertStringContainsString('.admin-sidebar li a', $legacy);
    }

    public function test_sidebar_groups_default_to_open_so_items_are_visible(): void
    {
        $blade = file_get_contents(resource_path('views/layouts/partials/sidebar-menu.blade.php'));

        // Default-open unless the user stored a collapsed state on the versioned key.
        $this->assertStringContainsString("stored === null ? true : stored === 'true'", $blade);
        $this->assertStringContainsString("sidebar-groups-v2-", $blade);
        $this->assertStringNotContainsString("_key: 'sidebar-group-{{ \$group['key'] }}'", $blade);
        $this->assertStringContainsString('open: true,', $blade);
    }
}
