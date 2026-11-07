<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Models\School;
use App\Reference\ClassCatalog;
use Illuminate\Database\Seeder;

/**
 * Depends on schools. One section per class in ClassCatalog.
 */
class SectionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            foreach (ClassCatalog::classes() as $class) {
                ReferenceUpsert::sync('sections', [
                    'school_id' => $school->id,
                    'name' => $class['name'],
                ], [
                    'status' => 1,
                ]);
            }
        }
    }
}
