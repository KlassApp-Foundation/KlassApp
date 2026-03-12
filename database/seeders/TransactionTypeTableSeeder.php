<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Realistic transaction types for Ugandan school payroll / payments
        $transactionTypes = [
            'Salary Payment',
            'Salary Advance / Loan',
            'Salary Deduction (NSSF / PAYE)',
            'Bonus / Gratuity',
            'Allowance (Housing / Transport)',
            'Refund / Salary Return',
            'Other Payments / Miscellaneous',
            'Fine / Penalty',
        ];

        $count = 0;

        foreach ($transactionTypes as $name) {
            DB::table('transaction_types')->updateOrInsert(
                ['name' => $name],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $count++;
        }

        $this->command->info("Seeded {$count} transaction types for payroll.");
    }
}