<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Seeder;

/**
 * Depends on AcademicYearsTableSeeder.
 * Three terms for each school's active year. The terms table is
 * unique on school_id + name, so terms are not repeated per year.
 * Exams require an academic_term_id.
 */
class AcademicTermsTableSeeder extends Seeder
{
    public function run(): void
    {
        $terms = [
            ['name' => 'Term 1', 'starts_on' => '2026-02-02 00:00:00', 'ends_on' => '2026-05-02 00:00:00', 'status' => 'current'],
            ['name' => 'Term 2', 'starts_on' => '2026-05-25 00:00:00', 'ends_on' => '2026-08-22 00:00:00', 'status' => 'next'],
            ['name' => 'Term 3', 'starts_on' => '2026-09-14 00:00:00', 'ends_on' => '2026-12-04 00:00:00', 'status' => 'next'],
        ];

        foreach (School::query()->where('status', 1)->get() as $school) {
            $year = AcademicYear::query()
                ->where('school_id', $school->id)
                ->where('status', 1)
                ->first();
            if ($year === null) {
                $this->command?->warn("No active academic year for school {$school->id}. Skipping terms.");

                continue;
            }

            foreach ($terms as $term) {
                ReferenceUpsert::sync('academic_terms', [
                    'school_id' => $school->id,
                    'name' => $term['name'],
                ], [
                    'academic_year_id' => $year->id,
                    'starts_on' => $term['starts_on'],
                    'ends_on' => $term['ends_on'],
                    'status' => $term['status'],
                ]);
            }
        }
    }
}
