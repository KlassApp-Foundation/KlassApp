<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use Illuminate\Database\Seeder;

class TransactionTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
            ReferenceUpsert::sync('transaction_types', ['name' => $name]);

            $count++;
        }

        $this->command->info("Seeded {$count} transaction types for payroll.");
    }
}