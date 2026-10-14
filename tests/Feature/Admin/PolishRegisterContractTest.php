<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class PolishRegisterContractTest extends TestCase
{
    public function test_K105_gender_legend_uses_counts_and_the_bar_has_a_gap(): void
    {
        $blade = file_get_contents(resource_path('views/admin/dashboard/dashboard_v2.blade.php'));
        $css = file_get_contents(public_path('css/dashboard-v2.css'));

        $this->assertStringContainsString('dashboard-v2-gender-legend', $blade);
        $this->assertStringContainsString("{{ (int) \$g['girls'] }} {{ (int) \$g['girls'] === 1 ? 'girl' : 'girls' }}", $blade);
        $this->assertStringNotContainsString("Girls {{ \$gp(\$g['girls']) }}%", $blade);
        $this->assertStringContainsString('gap:2px', $css);
    }

    public function test_A4_fees_chart_labels_only_the_collected_bar(): void
    {
        $blade = file_get_contents(resource_path('views/admin/dashboard/dashboard_v2.blade.php'));
        $chart = file_get_contents(resource_path('views/components/chart.blade.php'));

        $this->assertStringContainsString("'kaHideValueLabel' => true", $blade);
        $this->assertStringContainsString('expected ', $blade);
        $this->assertStringContainsString('ds.kaHideValueLabel', $chart);
    }

    public function test_A17_dashboard_greeting_is_26px_weight_600(): void
    {
        $css = file_get_contents(public_path('css/dashboard-v2.css'));

        $this->assertStringContainsString('.dv2-h1{font-family:Sora,var(--d-font-display,sans-serif);font-weight:600;font-size:26px;', $css);
        $this->assertStringNotContainsString('font-size:30px', $css);
    }

    public function test_K113_profile_tabs_are_underlined_44px_and_do_not_wrap(): void
    {
        $blade = file_get_contents(resource_path('views/components/person-profile.blade.php'));

        $this->assertStringContainsString('person-profile-tabs" aria-label="Profile" style="display:flex;flex-wrap:nowrap;', $blade);
        $this->assertStringContainsString('min-height:44px', $blade);
        $this->assertStringContainsString('border-bottom:2px solid', $blade);
        $this->assertStringNotContainsString('border-radius:999px;text-decoration:none;font-size:14px', $blade);
    }

    public function test_A15_people_search_is_44px_tall(): void
    {
        $css = file_get_contents(public_path('css/people-list.css'));

        $this->assertStringContainsString('.people-search input.ds-form-input { width: 100%; height: 44px; min-height: 44px;', $css);
    }

    public function test_K111_drawer_header_uses_the_horizontal_logo_and_a_close_icon(): void
    {
        $blade = file_get_contents(resource_path('views/layouts/admin/sidebar.blade.php'));

        $this->assertStringContainsString('images/klassapp-horizontal-dark.svg', $blade);
        $this->assertStringContainsString('aria-label="Close menu"', $blade);
        $this->assertStringContainsString('✕', $blade);
        $this->assertStringNotContainsString('>Close</button>', $blade);
    }
}
