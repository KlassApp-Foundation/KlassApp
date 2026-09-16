<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class StudentsRosterKitContractTest extends TestCase
{
    public function test_students_roster_blade_matches_kit_composition(): void
    {
        $blade = file_get_contents(resource_path('views/admin/member/index.blade.php'));

        $this->assertStringContainsString('ds-page-head', $blade);
        $this->assertStringContainsString('ds-page-head-title', $blade);
        $this->assertStringContainsString('Import list', $blade);
        $this->assertStringContainsString('Add student', $blade);
        $this->assertStringContainsString('dt-name-link', $blade);
        $this->assertStringContainsString('WhatsApp', $blade);
        $this->assertStringContainsString('selectable', $blade);
        $this->assertStringContainsString('sortable', $blade);
        $this->assertStringContainsString('dt-pagination', $blade);
        $this->assertStringContainsString('dt-checkbox', $blade);
        $this->assertStringContainsString('students-roster', $blade);
        $this->assertStringContainsString('No students match', $blade);
        $this->assertStringContainsString("url('/admin/student/edit/' . \$student->name)", $blade);
    }

    public function test_x_table_still_emits_ds_table_ledger_for_pulse(): void
    {
        $blade = file_get_contents(resource_path('views/components/table.blade.php'));

        $this->assertStringContainsString('ds-table-ledger', $blade);
        $this->assertStringContainsString('selectable', $blade);
        $this->assertStringContainsString('dt-cell-check', $blade);
    }

    public function test_pulse_toshi_css_still_greens_kpi_values_and_blurs_ledger_thead(): void
    {
        $css = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));

        $this->assertStringContainsString('.ds-kpi-card .ds-kpi-value', $css);
        $this->assertStringContainsString('color: #22C55E', $css);
        $this->assertStringContainsString('.ds-table-ledger thead', $css);
        $this->assertStringContainsString('backdrop-filter: blur(12px)', $css);
    }

    public function test_dashboard_refresh_preserves_reduced_motion_and_name_link(): void
    {
        $css = file_get_contents(public_path('css/dashboard-refresh.css'));

        $this->assertStringContainsString('.dt-name-link', $css);
        $this->assertStringContainsString('.dt-pagination', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
    }
}
