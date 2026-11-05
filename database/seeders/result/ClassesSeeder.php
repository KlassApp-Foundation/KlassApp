<?php

namespace Database\Seeders\result;

use App\Database\ReferenceUpsert;
use App\Models\School;
use App\Models\Standard;
use App\Reference\ClassCatalog;
use Illuminate\Database\Seeder;

/**
 * Depends on schools and standards. One class row per catalog class,
 * under that class's standard. Not a cross product of every standard.
 */
class ClassesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (School::query()->where('status', 1)->get() as $school) {
            $standards = Standard::query()->where('school_id', $school->id)->get()->keyBy('name');

            foreach (ClassCatalog::classes() as $class) {
                $standard = $standards->get($class['standard']);
                if ($standard === null) {
                    $this->command?->warn("Missing standard {$class['standard']} for school {$school->id}.");

                    continue;
                }

                ReferenceUpsert::sync('classes', [
                    'school_id' => $school->id,
                    'name' => $class['name'],
                ], [
                    'position' => $class['position'],
                    'status' => '1',
                    'standard_id' => $standard->id,
                ]);
            }
        }
    }
}
