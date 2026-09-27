<?php

namespace Tests\Feature\Reports;

use Tests\TestCase;

/**
 * Printed report text greys are #64748B (4.76:1 on white), not #94A3B8
 * (2.56:1) — 2026-09-27 handoff, item 5. The student-report .sign-line
 * border is a rule line, not text, and deliberately keeps #94A3B8.
 */
class PrintedReportGreyContrastTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(resource_path('views/'.$path));
    }

    public function test_student_report_text_greys_use_64748b(): void
    {
        $css = $this->source('admin/marks/student-report.blade.php');

        foreach (['.badge-year', '.info-label', '.marks-table td.empty', '.comments-label', '.footer-table td'] as $selector) {
            $this->assertMatchesRegularExpression('/'.preg_quote($selector, '/').'\s*\{[^}]*color:\s*#64748B;/', $css, $selector);
        }
        $this->assertMatchesRegularExpression('/\.sign-line\s*\{[^}]*border-bottom:\s*1px solid #94A3B8;/', $css);
        $this->assertSame(1, substr_count($css, '#94A3B8'), 'only the .sign-line rule may keep #94A3B8');
    }

    public function test_formal_and_missing_marks_have_no_94a3b8_text(): void
    {
        $this->assertMatchesRegularExpression('/\.ledger td\.empty\s*\{\s*color:\s*#64748B;/', $this->source('admin/marks/report-templates/formal.blade.php'));
        $this->assertStringNotContainsString('#94A3B8', $this->source('admin/marks/report-templates/formal.blade.php'));

        $missing = $this->source('admin/reports/missing-marks.blade.php');
        $this->assertStringNotContainsString('#94A3B8', $missing);
        $this->assertMatchesRegularExpression('/\.meta\s*\{[^}]*color:\s*#64748B;/', $missing);
        $this->assertMatchesRegularExpression('/\.footer\s*\{[^}]*color:\s*#64748B;/', $missing);
        $this->assertStringContainsString('font-size:10px;color:#64748B;', $missing);
    }
}
