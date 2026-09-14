<?php

namespace Tests\Feature\DesignSystem;

use Tests\TestCase;

/**
 * Locks the prefers-reduced-motion contract for the three infinite loops in
 * dashboard-refresh.css (LIVE badge sheen, LIVE pulsing dot, loading-dot bounce).
 *
 * These were documented as a known a11y gap until the reduced-motion media query
 * shipped — this test prevents the query from silently disappearing again.
 */
class ReducedMotionContractTest extends TestCase
{
    private function stylesheet(): string
    {
        return file_get_contents(public_path('css/dashboard-refresh.css'));
    }

    public function test_reduced_motion_media_query_exists(): void
    {
        $this->assertMatchesRegularExpression(
            '/@media\s*\(\s*prefers-reduced-motion:\s*reduce\s*\)/',
            $this->stylesheet()
        );
    }

    public function test_reduced_motion_disables_the_three_looping_animations(): void
    {
        $css = $this->stylesheet();

        $this->assertSame(
            1,
            preg_match(
                '/@media\s*\(\s*prefers-reduced-motion:\s*reduce\s*\)\s*\{(?P<body>.*)\}\s*$/s',
                $css,
                $m
            ),
            'expected a prefers-reduced-motion: reduce block at the end of dashboard-refresh.css'
        );

        $body = $m['body'];

        $this->assertMatchesRegularExpression(
            '/\.dashboard-shell--admin\s+\.dashboard-live-badge::after\s*\{[^}]*animation:\s*none/s',
            $body,
            'LIVE badge sheen must stop under reduced motion'
        );

        $this->assertMatchesRegularExpression(
            '/\.dashboard-live-dot\s*\{[^}]*animation:\s*none/s',
            $body,
            'LIVE pulsing dot must stop under reduced motion'
        );

        $this->assertMatchesRegularExpression(
            '/\.ds-loading-dot\s*\{[^}]*animation:\s*none/s',
            $body,
            'loading-dot bounce must stop under reduced motion'
        );
    }

    public function test_looping_keyframes_still_exist_for_default_motion(): void
    {
        $css = $this->stylesheet();

        foreach (['badge-sheen', 'pulse-dot', 'd-loadingBounce'] as $name) {
            $this->assertMatchesRegularExpression(
                '/@keyframes\s+'.preg_quote($name, '/').'\s*\{/',
                $css,
                "@keyframes {$name} must remain for users without reduced-motion"
            );
        }
    }
}
