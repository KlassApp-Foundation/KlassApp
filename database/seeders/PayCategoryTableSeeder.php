<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Realistic Ugandan school payment/fee categories
        $payCategories = [
            'Registration / Admission Fee',
            'Tuition / School Fees',
            'Development Levy / Capital Fund',
            'Meals / Lunch Fees',
            'Transport / Bus Fees',
            'Uniform & Books',
            'Medical / Health Insurance',
            'Co-curricular / Games & Sports',
            'Examination Fees (UNEB / Mock)',
            'Activity Fees',
            'Late Payment Penalty',
            'Miscellaneous / Other Fees',
            'User Defined / Custom',
            'Not Applicable',
            // Optional extras used in some schools
            'Boarding Fees',
            'Caution Money / Deposit (refundable)',
        ];

        foreach ($payCategories as $name) {
            DB::table('pay_categories')->updateOrInsert(
                ['name' => $name],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}