<?php

namespace Tests\Feature;

use Tests\TestCase;

class SidebarMenuHoverClassesTest extends TestCase
{
    public function test_teacher_menu_uses_dashboard_menu_item_not_legacy_purple_hover(): void
    {
        $html = (string) $this->view('layouts.teacher.menu');

        $this->assertStringNotContainsString('hover:bg-purple-900', $html);
        $this->assertStringContainsString('dashboard-menu-item', $html);
        $this->assertSame(11, substr_count($html, 'dashboard-menu-item'));
    }

    public function test_student_menu_uses_dashboard_menu_item_not_legacy_teal_hover(): void
    {
        $html = (string) $this->view('layouts.student.menu');

        $this->assertStringNotContainsString('hover:bg-teal-900', $html);
        $this->assertStringContainsString('dashboard-menu-item', $html);
        $this->assertSame(11, substr_count($html, 'dashboard-menu-item'));
    }
}
