<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayrollItemTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Realistic payroll items for Ugandan schools (private + semi-private)
        // These are standard/common — schools can add custom ones via admin
        $payrollItems = [
            // Earnings / Additions
            [
                'name' => 'Basic Salary',
                'key'  => 'BS',
                'type' => 'earning',
            ],
            [
                'name' => 'House Allowance',
                'key'  => 'HA',
                'type' => 'earning',
            ],
            [
                'name' => 'Transport Allowance',
                'key'  => 'TA',
                'type' => 'earning',
            ],
            [
                'name' => 'Responsibility / Responsibility Allowance',
                'key'  => 'RA',
                'type' => 'earning',
            ],
            [
                'name' => 'Overtime Pay',
                'key'  => 'OT',
                'type' => 'earning',
            ],
            [
                'name' => 'Performance Bonus / Gratuity',
                'key'  => 'PB',
                'type' => 'earning',
            ],

            // Deductions
            [
                'name' => 'NSSF Contribution (Employee 5%)',
                'key'  => 'NSSF',
                'type' => 'deduction',
            ],
            [
                'name' => 'PAYE (Income Tax)',
                'key'  => 'PAYE',
                'type' => 'deduction',
            ],
            [
                'name' => 'Loan Repayment / Salary Advance',
                'key'  => 'LOAN',
                'type' => 'deduction',
            ],
            [
                'name' => 'Absenteeism / Penalty',
                'key'  => 'ABS',
                'type' => 'deduction',
            ],
            [
                'name' => 'Other Deductions',
                'key'  => 'OD',
                'type' => 'deduction',
            ],

            // Special / Neutral
            [
                'name' => 'Not Applicable',
                'key'  => 'NA',
                'type' => 'neutral',
            ],
            [
                'name' => 'User Defined / Custom',
                'key'  => 'UD',
                'type' => 'neutral',
            ],
        ];

        foreach ($payrollItems as $item) {
            DB::table('payroll_items')->updateOrInsert(
                [
                    'key'  => $item['key'],
                    'name' => $item['name'],
                ],
                [
                    'type'       => $item['type'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}