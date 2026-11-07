<?php

namespace Database\Seeders\result;

use App\Database\ReferenceUpsert;
use Illuminate\Database\Seeder;

class RemarksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Realistic teacher feedback comments for Ugandan schools (demo variety)
        // These are the kinds of things teachers write on report cards/assignments
        $remarks = [
    // Top performers
    'Outstanding.',
    'Excellent.',
    'Very good.',
    // Middle performers
    'Good.',
    'Fair.',
    'Satisfactory.',
    // Low performers
    'Needs improvement.',
    'Poor.',
    'Very poor.',
];

        foreach ($remarks as $remark) {
            ReferenceUpsert::sync('remarks', ['remark' => $remark]);
        }
        $this->command->info("Total unique comments seeded!");
    }
}