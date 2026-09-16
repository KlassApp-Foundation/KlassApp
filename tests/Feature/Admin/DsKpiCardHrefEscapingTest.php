<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * KPI cards must emit a real href — never a Blade-escaped quote wrapper that
 * browsers resolve as /admin/%22https:…%22 (404).
 */
class DsKpiCardHrefEscapingTest extends TestCase
{
    public function test_linked_kpi_renders_unescaped_href(): void
    {
        $url = 'https://example.test/admin/students';
        $html = Blade::render(
            '<x-ds-kpi-card icon="users" value="9" label="Students" color="blue" :link="$link" />',
            ['link' => $url]
        );

        $this->assertStringContainsString('href="'.$url.'"', $html);
        $this->assertStringNotContainsString('href=&quot;', $html);
        $this->assertStringNotContainsString('href="%22', $html);
        $this->assertStringNotContainsString('href="&quot;', $html);
        $this->assertMatchesRegularExpression('/<a\s+href="'.preg_quote($url, '/').'"/', $html);
    }

    public function test_unlinked_kpi_renders_div_not_anchor(): void
    {
        $html = Blade::render(
            '<x-ds-kpi-card icon="users" value="0" label="Parents" color="green" />'
        );

        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringContainsString('ds-kpi-card', $html);
        $this->assertStringContainsString('<div', $html);
    }

    public function test_component_source_does_not_assemble_href_into_escaped_attrs_string(): void
    {
        $src = file_get_contents(resource_path('views/components/ds-kpi-card.blade.php'));
        $this->assertNotFalse($src);
        $this->assertStringNotContainsString("\$attrs = 'href=", $src);
        $this->assertStringContainsString('href="{{ $link }}"', $src);
    }
}
