<?php

namespace App\Reference;

/**
 * Sitting types. End of term and end of year contribute to the
 * report total. The duplicate "Weekly Exams" / WE row that the
 * old seeder inserted twice is not repeated.
 */
final class ExamTypeCatalog
{
    /**
     * @return list<array{name: string, code: string, contributes_to_report_total: bool}>
     */
    public static function all(): array
    {
        return [
            ['name' => 'Beginning Of Term', 'code' => 'BOT', 'contributes_to_report_total' => false],
            ['name' => 'Weekly Exams', 'code' => 'WE', 'contributes_to_report_total' => false],
            ['name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => false],
            ['name' => 'Monthly Exams', 'code' => 'ME', 'contributes_to_report_total' => false],
            ['name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => true],
            ['name' => 'Mock Exam', 'code' => 'MOCK', 'contributes_to_report_total' => false],
            ['name' => 'Pre Mock Exam', 'code' => 'PreMOCK', 'contributes_to_report_total' => false],
            ['name' => 'End Of Year', 'code' => 'FINAL', 'contributes_to_report_total' => true],
        ];
    }
}
