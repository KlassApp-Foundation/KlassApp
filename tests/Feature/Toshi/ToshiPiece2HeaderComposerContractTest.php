<?php

namespace Tests\Feature\Toshi;

use Tests\TestCase;

/**
 * Piece 2 PR1 — header/composer chrome + Pulse freeze canary on published CSS.
 */
class ToshiPiece2HeaderComposerContractTest extends TestCase
{
    public function test_published_toshi_ui_css_keeps_pulse_ledger_blur_canary(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString('FROZEN — Pulse design system', $css);
        $this->assertStringContainsString('.ds-table-ledger thead', $css);
        $this->assertMatchesRegularExpression(
            '/\.ds-table-ledger thead\s*\{[^}]*backdrop-filter:\s*blur\(12px\)/s',
            $css
        );
        $this->assertStringContainsString('.ds-kpi-card', $css);
    }

    public function test_published_toshi_ui_css_includes_piece2_header_composer_tokens(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertNotFalse($css);
        $this->assertStringContainsString('PIECE 2 — Toshi panel chrome', $css);
        $this->assertStringContainsString('--toshi-clay: #c96442', $css);
        $this->assertStringContainsString('.toshi-confirm-chips', $css);
        $this->assertStringContainsString('.toshi-composer--awaiting-confirm', $css);
        $this->assertStringContainsString('border-radius: 16px', $css);
    }

    public function test_source_and_published_toshi_ui_css_match(): void
    {
        $source = file_get_contents(base_path('packages/toshi-ui/resources/css/toshi-ui.css'));
        $published = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertSame($source, $published, 'Run: php artisan vendor:publish --tag=toshi-ui-css --force');
    }
}
