<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Models\School;
use App\Reference\ClassCatalog;
use Illuminate\Database\Seeder;

/**
 * Depends on schools. One standard (grading band) per school.
 */
class StandardsTableSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            foreach (ClassCatalog::standards() as $standard) {
                ReferenceUpsert::sync('standards', [
                    'school_id' => $school->id,
                    'name' => $standard['name'],
                ], [
                    'order' => $standard['order'],
                    'status' => 1,
                    'deleted_at' => null,
                ]);
            }
        }

        $this->command?->info('Seeded '.count(ClassCatalog::standards()).' standards per active school.');
    }
}
