<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Reference\ClassCatalog;
use Illuminate\Database\Seeder;

/**
 * Depends on schools, an active academic year, sections, and standards.
 * Each class is linked to its own standard. Subjects are not crossed
 * with every standard.
 */
class SubjectsTableSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [];
        foreach (ClassCatalog::classes() as $class) {
            $catalog[$class['name']] = $class;
        }

        foreach (School::query()->where('status', 1)->get() as $school) {
            $year = AcademicYear::query()
                ->where('school_id', $school->id)
                ->where('status', 1)
                ->first();
            if ($year === null) {
                $this->command?->warn("No active academic year for school {$school->id}. Skipping subjects.");

                continue;
            }

            $standards = Standard::query()->where('school_id', $school->id)->get()->keyBy('name');
            $sections = Section::query()->where('school_id', $school->id)->get();

            foreach ($sections as $section) {
                $class = $catalog[$section->name] ?? null;
                if ($class === null) {
                    continue;
                }
                $standard = $standards->get($class['standard']);
                if ($standard === null) {
                    $this->command?->warn("Missing standard {$class['standard']} for school {$school->id}.");

                    continue;
                }

                foreach ($class['subjects'] as $subject) {
                    $type = $subject === 'Kiswahili' ? 'elective' : 'core';
                    ReferenceUpsert::sync('subjects', [
                        'school_id' => $school->id,
                        'section_id' => $section->id,
                        'name' => $subject,
                    ], [
                        'academic_year_id' => $year->id,
                        'standard_id' => $standard->id,
                        'code' => strtoupper(substr($subject, 0, 3)).'-'.$section->id,
                        'type' => $type,
                        'status' => 1,
                    ]);
                }
            }
        }
    }
}
