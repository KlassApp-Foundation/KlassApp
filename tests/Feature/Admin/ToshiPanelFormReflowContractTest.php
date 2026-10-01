<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * Soft-launch 1c: with Toshi dock open, admin create/list forms must reflow
 * (stack fields) instead of clipping Gender/Country/City/Joining Date, and
 * Address must sit inside the student create shell — not beside Gender.
 */
class ToshiPanelFormReflowContractTest extends TestCase
{
    public function test_published_css_stacks_lg_flex_rows_when_toshi_dock_open(): void
    {
        $source = file_get_contents(base_path('packages/toshi-ui/resources/css/toshi-ui.css'));
        $published = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));

        $this->assertSame($source, $published, 'Run: php artisan vendor:publish --tag=toshi-ui-css --force');

        foreach ([$source, $published] as $css) {
            $this->assertStringContainsString('toshi-form-reflow', $css);
            $this->assertMatchesRegularExpression(
                '/body:not\(\.toshi-collapsed\)\s+main\s+\.flex\.lg\\\\:flex-row[^\{]*\{[^}]*flex-direction:\s*column\s*!important/s',
                $css,
            );
            $this->assertMatchesRegularExpression(
                '/body:not\(\.toshi-collapsed\)\s+main\s+\[class\*="lg:w-1\/"\][^\{]*\{[^}]*width:\s*100%\s*!important/s',
                $css,
            );
        }
    }

    public function test_student_create_vue_keeps_address_out_of_gender_flex_row(): void
    {
        $vue = file_get_contents(resource_path('assets/js/components/student/Create.vue'));

        // Gender row must close before Address; Address is a full-width block
        // (optional HTML comment may sit between the closing divs and Address).
        // Do not use Blade {{-- --}} inside .vue — Vite parses {{ as interpolation.
        $this->assertStringNotContainsString('{{--', $vue);
        $this->assertMatchesRegularExpression(
            '/for="gender".*?<\/div>\s*<\/div>\s*<\/div>\s*(?:<!--.*?-->\s*)?<div[^>]*data-testid="student-create-address"/s',
            $vue,
            'Gender lg:flex-row must close before the Address block',
        );
        $this->assertStringContainsString('data-testid="student-create-address"', $vue);
        $this->assertStringContainsString('name="address"', $vue);
        $this->assertStringNotContainsString('<portal-target name="address"', $vue);
    }

    public function test_student_create_blade_no_longer_portals_address_outside_shell(): void
    {
        $blade = file_get_contents(resource_path('views/admin/member/create.blade.php'));

        $this->assertStringNotContainsString('portal to="address"', $blade);
        $this->assertStringContainsString('<create-member', $blade);
    }

    public function test_teacher_create_drops_forced_horizontal_scroll_shell(): void
    {
        $vue = file_get_contents(resource_path('assets/js/components/teacher/Create.vue'));

        $this->assertStringNotContainsString('overflow-x-scroll', $vue);
        $this->assertStringContainsString('overflow-x-auto', $vue);
    }
}
