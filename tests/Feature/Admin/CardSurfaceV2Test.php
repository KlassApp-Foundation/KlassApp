<?php

/**
 * SPDX-License-Identifier: MIT
 */

namespace Tests\Feature\Admin;

use Tests\TestCase;

class CardSurfaceV2Test extends TestCase
{
    public function test_dashboard_tiles_use_the_shared_surface_tokens(): void
    {
        $path = dirname(__DIR__, 3).'/public/css/dashboard-v2.css';
        $css = file_get_contents($path);

        $this->assertNotFalse($css);
        $this->assertStringContainsString('--ka-surface-dot:', $css);
        $this->assertStringContainsString('--ka-surface-hover-tint:', $css);
        $this->assertStringContainsString('--ka-surface-motion: 150ms', $css);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);

        $this->assertMatchesRegularExpression(
            '/\.dv2-kpi,\s*\.dv2-qt,\s*\.dv2-qa \.dv2-btn \{[^}]*--ka-surface-border/',
            $css
        );
        $this->assertDoesNotMatchRegularExpression('/\.dv2-card::before/', $css);
    }
}
