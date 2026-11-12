<?php

namespace Database\Seeders\result;

use App\Services\Academics\ExamTypeCatalogSync;
use Illuminate\Database\Seeder;

class ExamTypes extends Seeder
{
    public function run(): void
    {
        $touched = app(ExamTypeCatalogSync::class)->syncShared();

        $this->command?->info('Exam types seeded. '.$touched.' shared row(s) written.');
    }
}
