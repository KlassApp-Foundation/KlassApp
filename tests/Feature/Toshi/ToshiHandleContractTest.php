<?php

namespace Tests\Feature\Toshi;

use Tests\TestCase;

class ToshiHandleContractTest extends TestCase
{
    public function test_collapsed_handle_is_a_focusable_k_tab_from_1280(): void
    {
        $embed = file_get_contents(resource_path('views/layouts/partials/toshi-embed.blade.php'));
        $css = file_get_contents(base_path('packages/toshi-ui/resources/css/toshi-ui.css'));
        $handle = explode('TOSHI HANDLE', $css, 2)[1] ?? '';

        $this->assertStringContainsString('<button type="button" id="toshi-toggle"', $embed);
        $this->assertStringContainsString('aria-label="Open Toshi"', $embed);
        $this->assertStringContainsString('Toshi, coming soon', $embed);
        $this->assertStringContainsString('toshi-handle-mark', $embed);
        $this->assertStringNotContainsString('▶', $embed);

        $this->assertNotSame('', $handle);
        $this->assertStringContainsString('width: 40px', $handle);
        $this->assertStringContainsString('height: 96px', $handle);
        $this->assertStringContainsString('background: #fff', $handle);
        $this->assertStringContainsString('border: 1px solid #E2E8F0', $handle);
        $this->assertStringContainsString('border-radius: 8px 0 0 8px', $handle);
        $this->assertStringContainsString('color: #15803D', $handle);
        $this->assertStringContainsString(':focus-visible', $handle);
        $this->assertStringContainsString('@media (max-width: 1279px)', $handle);
        $this->assertStringNotContainsString('#c96442', $handle);
        $this->assertStringNotContainsString('data-toshi-mode="preview"', $handle);
    }
}
