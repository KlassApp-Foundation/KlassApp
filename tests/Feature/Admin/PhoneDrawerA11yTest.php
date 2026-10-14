<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class PhoneDrawerA11yTest extends TestCase
{
    public function test_phone_drawer_is_a_modal_dialog_with_a_focus_trap(): void
    {
        $blade = file_get_contents(resource_path('views/layouts/admin/sidebar.blade.php'));
        $this->assertStringContainsString('id="res_sidebar"', $blade);
        $this->assertStringContainsString('role="dialog"', $blade);
        $this->assertStringContainsString('aria-modal="true"', $blade);
        $this->assertStringContainsString('aria-label="Main menu"', $blade);

        $js = file_get_contents(public_path('js/custom.js'));
        $this->assertStringContainsString('setAttribute(\'inert\'', $js);
        $this->assertStringContainsString('function trapDrawerTab', $js);

        $css = file_get_contents(base_path('packages/toshi-ui/resources/css/toshi-ui.css'));
        $this->assertStringContainsString('[data-toshi-resize-handle] { display: none !important; }', $css);
    }
}
