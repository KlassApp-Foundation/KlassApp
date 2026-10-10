<?php

namespace Tests\Feature\Navigation;

use Tests\TestCase;

/**
 * K55/K63 shell fix: the compact sidebar renders icon-LEFT rows (legacy
 * admin.css forced flex-direction: column), and groups show their items by
 * default ("group labels with items visible").
 */
class ShellSidebarLayoutFixTest extends TestCase
{
    public function test_sidebar_items_override_the_legacy_column_layout(): void
    {
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));

        $this->assertStringContainsString('#admin-sidebar.admin-sidebar li a', $css);
        $this->assertStringContainsString('flex-direction: row;', $css);
        // The legacy rule that caused the stacked layout still exists in admin.css;
        // our override must come from the later sheet (dashboard-refresh.css is
        // linked after admin.css? it is loaded per layout order).
        $legacy = file_get_contents(public_path('css/admin.css'));
        $this->assertStringContainsString('.admin-sidebar li a', $legacy);
    }

    public function test_sidebar_groups_default_to_open_so_items_are_visible(): void
    {
        $blade = file_get_contents(resource_path('views/layouts/partials/sidebar-menu.blade.php'));
        $this->assertStringContainsString("open: true,", $blade);
        $this->assertStringNotContainsString("open: false,", $blade);
    }
}
