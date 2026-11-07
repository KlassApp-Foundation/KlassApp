<?php

namespace Database\Seeders\result;

use App\Database\ReferenceUpsert;
use App\Reference\ExamTypeCatalog;
use Illuminate\Database\Seeder;

class ExamTypes extends Seeder
{
    public function run(): void
    {
        foreach (ExamTypeCatalog::all() as $type) {
            ReferenceUpsert::sync('exam_types', ['code' => $type['code']], [
                'name' => $type['name'],
                'contributes_to_report_total' => $type['contributes_to_report_total'],
            ]);
        }

        $this->command?->info('Exam types seeded.');
    }
}
