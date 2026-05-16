<?php

namespace Database\Seeders\result;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;

class RemarksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

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

        $seededCount = 0;

            foreach ($remarks as $remark) {
                DB::table('remarks')->updateOrInsert(
                    [
                        'remark'   => $remark,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $seededCount++;
            }
        $this->command->info("Total unique comments seeded!");
    }
}