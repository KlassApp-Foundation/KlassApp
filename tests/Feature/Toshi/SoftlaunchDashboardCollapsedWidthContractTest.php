<?php
/**
 * SPDX-License-Identifier: MIT
 */

namespace Tests\Feature\Toshi;

use Tests\TestCase;

/**
 * Soft-launch 2: with Toshi collapsed, main content must reclaim full width
 * (no reserved dock margin).
 */
class SoftlaunchDashboardCollapsedWidthContractTest extends TestCase
{
    public function test_toshi_ui_css_zeroes_main_margin_when_collapsed(): void
    {
        $css = file_get_contents(base_path('packages/toshi-ui/resources/css/toshi-ui.css'));
        $this->assertIsString($css);
        $this->assertStringContainsString('Soft-launch 2', $css);
        $this->assertMatchesRegularExpression(
            '/body\.toshi-collapsed main[\s\S]*?margin-right:\s*0\s*!important/',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/body\.toshi-collapsed \.dashboard-content-area[\s\S]*?width:\s*100%\s*!important/',
            $css
        );
    }

    public function test_published_toshi_ui_css_matches_collapsed_full_width_rules(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertIsString($css);
        $this->assertStringContainsString('Soft-launch 2', $css);
        $this->assertMatchesRegularExpression(
            '/html\.toshi-collapsed main[\s\S]*?margin-right:\s*0\s*!important/',
            $css
        );
    }
}
