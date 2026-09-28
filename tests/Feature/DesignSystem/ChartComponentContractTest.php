<?php

namespace Tests\Feature\DesignSystem;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Tests\TestCase;

/**
 * Locks the <x-chart> contract from the 2026-09-27 chart-colour handoff:
 * a required empty-message, the opt-in inline value-label plugin (no
 * chartjs-plugin-datalabels dependency) and the AA fallback palette.
 */
class ChartComponentContractTest extends TestCase
{
    private function config(string $html): array
    {
        $this->assertSame(1, preg_match('/data-chart-config="([^"]*)"/', $html, $m), 'no chart config rendered');

        return json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), true);
    }

    public function test_missing_empty_message_throws_in_testing(): void
    {
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('requires an empty-message');

        Blade::render('<x-chart type="bar" :labels="[\'A\']" :datasets="[[\'data\' => [1]]]" />');
    }

    public function test_empty_message_renders_when_every_dataset_is_empty(): void
    {
        $html = Blade::render('<x-chart type="bar" empty-message="No marks recorded yet" :labels="[]" :datasets="[[\'data\' => []]]" />');

        $this->assertStringContainsString('No marks recorded yet', $html);
        $this->assertStringNotContainsString('<canvas', $html);
    }

    public function test_value_label_plugin_is_inline_and_opt_in(): void
    {
        $component = file_get_contents(resource_path('views/components/chart.blade.php'));

        $this->assertStringContainsString("id: 'dsValueLabels'", $component);
        $this->assertStringContainsString('afterDatasetsDraw', $component);
        $this->assertStringContainsString("'600 11px \"DM Sans\", sans-serif'", $component);
        $this->assertStringContainsString("'#1E293B'", $component);
        $this->assertStringContainsString('bar.y - 4', $component);
        $this->assertStringNotContainsString('chartjs-plugin-datalabels', file_get_contents(base_path('package.json')));
    }

    public function test_eot_card_enables_value_labels_with_aa_hues(): void
    {
        $card = file_get_contents(resource_path('views/admin/reports/_eot-kpi-card.blade.php'));

        $this->assertStringContainsString("'dsValueLabels' => ['display' => true]", $card);
        $this->assertStringNotContainsString('#CA8A04', $card);
        $this->assertSame(2, substr_count($card, "'#A16207'"), 'PHP and JS hue lists must stay in sync');
    }

    public function test_dataset_without_colour_gets_the_aa_fallback_palette(): void
    {
        $html = Blade::render(
            '<x-chart type="bar" empty-message="None" :labels="[\'A\']" :datasets="$ds" />',
            ['ds' => [['data' => [1]], ['data' => [2]], ['data' => [3], 'backgroundColor' => '#304ffe']]]
        );
        $ds = $this->config($html)['data']['datasets'];

        $this->assertSame('#1E6FD9', $ds[0]['backgroundColor']);
        $this->assertSame('#B45309', $ds[1]['backgroundColor']);
        $this->assertSame('#304ffe', $ds[2]['backgroundColor']);
        $this->assertArrayNotHasKey('borderColor', $ds[2]);
    }
}
