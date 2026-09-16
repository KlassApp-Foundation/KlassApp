<?php

namespace Tests\Feature\DesignSystem;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Locks the CSS class contract of the two DS components whose props had drifted
 * away from the stylesheet.
 *
 * Background: `<x-table>` declared `striped` and `hover` in `@props` but never
 * referenced either in the template, so 8 production views passed them and got
 * nothing. `.ds-btn-md` — `<x-button>`'s default size — had its rule dropped
 * from `dashboard-refresh.css` in the 8d6fb9eb redesign.
 */
class TableAndButtonClassContractTest extends TestCase
{
    private function renderTable(string $attributes = ''): string
    {
        return Blade::render(
            '<x-table :headers="$headers" '.$attributes.'><tr><td>a</td></tr><tr><td>b</td></tr></x-table>',
            ['headers' => ['Student', 'Amount']]
        );
    }

    private function tableClassAttribute(string $html): string
    {
        $this->assertSame(1, preg_match('/<table class="([^"]*)"/', $html, $m), 'no <table class="…"> in output');

        return $m[1];
    }

    private function stylesheet(): string
    {
        return file_get_contents(public_path('css/dashboard-refresh.css'));
    }

    public function test_striped_prop_emits_the_striped_class(): void
    {
        $classes = $this->tableClassAttribute($this->renderTable('striped'));

        $this->assertStringContainsString('ds-table-striped', $classes);
    }

    public function test_striped_class_is_absent_by_default(): void
    {
        $classes = $this->tableClassAttribute($this->renderTable());

        $this->assertStringNotContainsString('ds-table-striped', $classes);
    }

    /**
     * The striping rule the prop now switches on must actually exist, or the
     * class is decorative again.
     */
    public function test_the_striped_css_rule_exists(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.ds-table-striped\s+tbody\s+tr:nth-child\(even\)/',
            $this->stylesheet()
        );
    }

    /**
     * Row hover is intrinsic to `.ds-table-ledger`, so the component must not
     * grow a `hover` prop again — and the unconditional rule must stay put,
     * because 8 raw `<table class="ds-table-ledger">` views depend on it.
     */
    public function test_hover_is_intrinsic_to_the_ledger_and_not_a_prop(): void
    {
        $component = file_get_contents(resource_path('views/components/table.blade.php'));
        $this->assertStringNotContainsString("'hover'", $component);

        $classes = $this->tableClassAttribute($this->renderTable());
        $this->assertStringNotContainsString('ds-table-hover', $classes);

        $this->assertMatchesRegularExpression(
            '/\.ds-table-ledger\s+tbody\s+tr:hover/',
            $this->stylesheet()
        );
    }

    public function test_base_and_density_classes_are_unchanged(): void
    {
        $default = $this->tableClassAttribute($this->renderTable());
        $this->assertStringContainsString('ds-table-ledger', $default);
        $this->assertStringContainsString('dt-comfortable', $default);
        $this->assertStringContainsString('ds-table-card-mobile', $default);

        $compact = $this->tableClassAttribute($this->renderTable('density="compact"'));
        $this->assertStringContainsString('dt-compact', $compact);
        $this->assertStringNotContainsString('dt-comfortable', $compact);
    }

    public function test_striped_composes_with_density_and_extra_classes(): void
    {
        $classes = $this->tableClassAttribute(
            $this->renderTable('striped density="compact" class="mt-4"')
        );

        foreach (['ds-table-ledger', 'dt-compact', 'ds-table-striped', 'mt-4'] as $expected) {
            $this->assertStringContainsString($expected, $classes);
        }
    }

    public function test_button_default_size_emits_the_md_class(): void
    {
        $html = Blade::render('<x-button variant="primary">Save</x-button>');

        $this->assertStringContainsString('ds-btn-md', $html);
    }

    /**
     * `.ds-btn-md` restates the `.ds-btn` base values rather than overriding
     * them — asserting both keeps the default size from silently drifting away
     * from the base rule again.
     */
    public function test_the_md_size_rule_exists_and_matches_the_base_metrics(): void
    {
        $css = $this->stylesheet();

        $this->assertMatchesRegularExpression(
            '/\.ds-btn-md\s*\{\s*padding:\s*8px\s+18px;\s*font-size:\s*0\.85rem;\s*\}/',
            $css,
            '.ds-btn-md rule missing or changed — it was dropped once before in 8d6fb9eb'
        );

        $this->assertMatchesRegularExpression('/\.ds-btn\s*\{[^}]*padding:\s*8px\s+18px;/s', $css);
        $this->assertMatchesRegularExpression('/\.ds-btn\s*\{[^}]*font-size:\s*0\.85rem;/s', $css);
    }

    public function test_all_three_button_sizes_have_rules(): void
    {
        $css = $this->stylesheet();

        foreach (['sm', 'md', 'lg'] as $size) {
            $this->assertMatchesRegularExpression(
                '/\.ds-btn-'.$size.'\s*\{[^}]*font-size:/',
                $css,
                ".ds-btn-{$size} has no font-size rule"
            );
        }
    }
}
