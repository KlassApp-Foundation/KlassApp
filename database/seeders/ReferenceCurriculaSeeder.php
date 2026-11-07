<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Reference\CurriculumCatalog;
use Illuminate\Database\Seeder;

class ReferenceCurriculaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CurriculumCatalog::all() as $curriculum) {
            ReferenceUpsert::sync('reference_curricula', ['code' => $curriculum['code']], [
                'name' => $curriculum['name'],
            ]);
        }
    }
}
