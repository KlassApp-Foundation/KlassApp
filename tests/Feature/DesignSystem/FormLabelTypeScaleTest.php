<?php

namespace Tests\Feature\DesignSystem;

use Tests\TestCase;

/**
 * .ds-form-label sits on the type scale (0.85rem) rather than the off-scale
 * 0.82rem — 2026-09-27 handoff, token decision 3a.
 */
class FormLabelTypeScaleTest extends TestCase
{
    public function test_form_label_uses_the_085rem_scale_step(): void
    {
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));

        $this->assertSame(1, preg_match('/\.ds-form-label\s*\{([^}]*)\}/', $css, $m), '.ds-form-label rule missing');
        $this->assertStringContainsString('font-size: 0.85rem;', $m[1]);
        $this->assertStringNotContainsString('0.82rem', $m[1]);
    }
}
