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
                'name' => 'Beginning Of Term',
                'code' => 'BOT',
            ],
            [
                'name' => 'Weekly Exams',
                'code' => 'WE',
            ],
            [
                'name' => 'Mid Term',
                'code' => 'MID',
            ],
             [
                'name' => 'Monthly Exams',
                'code' => 'ME',
            ],
             [
                'name' => 'Weekly Exams',
                'code' => 'WE',
            ],
            [
                'name' => 'End Of Term',
                'code' => 'EOT',
            ],
            [
                'name' => 'Mock Exam',
                'code' => 'MOCK',
            ],
            [
                'name' => 'Pre Mock Exam',
                'code' => 'PreMOCK',
            ],
            [
                'name' => 'Final Exam',
                'code' => 'FINAL',
            ],
        ];

        foreach ($types as $type){
            DB::table('exam_types')->insert($type);
        }
        $this->command->info("Exam types seeded successfully!");
    }
}