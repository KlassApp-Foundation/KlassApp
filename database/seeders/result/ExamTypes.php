<?php

namespace Database\Seeders\result;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExamTypes extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Beginning of Term',
                'code' => 'BOT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Weekly Exams',
                'code' => 'WE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mid Term',
                'code' => 'MID',
                'created_at' => now(),
                'updated_at' => now(),
            ],
             [
                'name' => 'Monthly Exams',
                'code' => 'ME',
                'created_at' => now(),
                'updated_at' => now(),
            ],
             [
                'name' => 'Weekly Exams',
                'code' => 'WE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'End of Term',
                'code' => 'EOT',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mock Exam',
                'code' => 'MOCK',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Final Exam',
                'code' => 'FINAL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('exam_types')->insert($types);
    }
}