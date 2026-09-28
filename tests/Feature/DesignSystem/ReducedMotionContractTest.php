<?php

namespace Tests\Feature\DesignSystem;

use Tests\TestCase;

/**
 * Locks the prefers-reduced-motion contract for every infinite loop in
 * dashboard-refresh.css: loading-dot bounce, save-indicator d-pulse, and
 * Toshi plan-card toshi-spin. (The LIVE badge sheen/dot entries were removed
 * with the badge itself — 2026-09-27 UI-polish pass; the badge no longer
 * renders anywhere, so it has no motion contract left to lock.)
 */
class ReducedMotionContractTest extends TestCase
{
    private function stylesheet(): string
    {
        return file_get_contents(public_path('css/dashboard-refresh.css'));
    }

    private function reducedMotionBody(): string
    {
        $this->assertSame(
            1,
            preg_match(
                '/@media\s*\(\s*prefers-reduced-motion:\s*reduce\s*\)\s*\{(?P<body>.*)\}\s*$/s',
                $this->stylesheet(),
                $m
            ),
            'expected a prefers-reduced-motion: reduce block at the end of dashboard-refresh.css'
        );

        return $m['body'];
    }

    public function test_reduced_motion_media_query_exists(): void
    {
        $this->assertMatchesRegularExpression(
            '/@media\s*\(\s*prefers-reduced-motion:\s*reduce\s*\)/',
            $this->stylesheet()
        );
    }

    public function test_reduced_motion_disables_all_remaining_looping_animations(): void
    {
        $body = $this->reducedMotionBody();

        $this->assertStringNotContainsString('dashboard-live-badge', $body, 'LIVE badge removed 2026-09-27; its reduced-motion entries were removed with it');

        $this->assertMatchesRegularExpression(
            '/\.ds-loading-dot\s*\{[^}]*animation:\s*none/s',
            $body,
            'loading-dot bounce must stop under reduced motion'
        );

        $this->assertMatchesRegularExpression(
            '/\.ds-save-indicator--saving\s+\.ds-save-indicator__dot\s*\{[^}]*animation:\s*none/s',
            $body,
            'save-indicator d-pulse must stop under reduced motion'
        );

        $this->assertMatchesRegularExpression(
            '/\[data-toshi-root\]\s+\.toshi-plan-card-processing::before\s*\{[^}]*animation:\s*none/s',
            $body,
            'toshi-spin must stop under reduced motion'
        );
    }

    public function test_looping_keyframes_still_exist_for_default_motion(): void
    {
        $css = $this->stylesheet();

        foreach (['d-loadingBounce', 'd-pulse', 'toshi-spin'] as $name) {
            $this->assertMatchesRegularExpression(
                '/@keyframes\s+'.preg_quote($name, '/').'\s*\{/',
                $css,
                "@keyframes {$name} must remain for users without reduced-motion"
            );
        }
    }
}
