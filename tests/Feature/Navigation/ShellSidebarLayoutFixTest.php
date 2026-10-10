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

    public function test_sidebar_v3_does_not_restore_old_group_state(): void
    {
        $blade = file_get_contents(resource_path('views/layouts/partials/sidebar-menu-v3.blade.php'));
        $nav = config('navigation.roles.admin');

        $this->assertSame('v3', $nav['layout']);
        $this->assertStringNotContainsString("localStorage.setItem('ka:sidebar:v3', '1')", $blade);
        $this->assertStringNotContainsString('sidebar-groups-v2', $blade);
        $this->assertStringNotContainsString('sidebar-group-', $blade);

        $rows = [];
        foreach ($nav['sections'] as $section) {
            foreach ($section['rows'] as $row) {
                $rows[] = $row['label'];
            }
        }
        $this->assertCount(12, $rows);
        $this->assertSame([
            'Dashboard',
            'Students',
            'Teachers and staff',
            'Parents',
            'Classes and subjects',
            'Attendance',
            'Exams and reports',
            'Library',
            'Fees',
            'Messages',
            'Approvals',
            'Settings',
        ], $rows);
    }
}
