<?php

namespace Tests\Feature;

use Tests\TestCase;

class SidebarMenuHoverClassesTest extends TestCase
{
    public function test_teacher_menu_uses_dashboard_menu_item_not_legacy_purple_hover(): void
    {
        $html = (string) $this->view('layouts.partials.sidebar-menu', ['role' => 'teacher']);

        $this->assertStringNotContainsString('hover:bg-purple-900', $html);
        $this->assertStringContainsString('dashboard-menu-item', $html);

        // Expectation comes from the menu data, not a hardcoded number. Items gated by
        // an auth-dependent `condition` are hidden on an unauthenticated render (teacher
        // has two: Report Cards and Class Streams, both class_teacher only), so count the
        // unconditional items and assert one class occurrence per rendered item.
        $items = collect(config('navigation.roles.teacher.items'))
            ->reject(fn ($item) => isset($item['condition']));

        $this->assertSame($items->count(), substr_count($html, 'dashboard-menu-item'));
    }

    public function test_student_menu_uses_dashboard_menu_item_not_legacy_teal_hover(): void
    {
        $html = (string) $this->view('layouts.partials.sidebar-menu', ['role' => 'student']);

        $this->assertStringNotContainsString('hover:bg-teal-900', $html);
        $this->assertStringContainsString('dashboard-menu-item', $html);

        $items = collect(config('navigation.roles.student.items'))
            ->reject(fn ($item) => isset($item['condition']));

        $this->assertSame($items->count(), substr_count($html, 'dashboard-menu-item'));
    }
}
