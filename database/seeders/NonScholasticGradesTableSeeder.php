<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\AcademicYear;

class NonScholasticGradesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Non-scholastic grading bands (Uganda-relevant categories)
        // You can easily add/edit bands here
        $gradingBands = [
            [
                'grade_name'     => '1-5',  // Lower primary
                'keys'           => json_encode([
                    'art_education',
                    'physical_education',
                    'work_education',
                ]),
                'grades_details' => json_encode([
                    'A' => 'Outstanding',
                    'B' => 'Above Average',
                    'C' => 'Needs Improvement',
                ]),
            ],

            [
                'grade_name'     => '6-8',  // Upper primary
                'keys'           => json_encode([
                    'thinking_skills',
                    'social_skills',
                    'emotional_skills',
                ]),
                'grades_details' => json_encode([
                    'A' => 'Outstanding',
                    'B' => 'Above Average',
                    'C' => 'Needs Improvement',
                ]),
            ],

            [
                'grade_name'     => '9-10', // Lower secondary (O-level)
                'keys'           => json_encode([
                    'attitude_values',
                    'wellness_education',
                    'service_activities',
                ]),
                'grades_details' => json_encode([
                    'A' => 'Outstanding',
                    'B' => 'Above Average',
                    'C' => 'Needs Improvement',
                ]),
            ],

            [
                'grade_name'     => '11-12', // Upper secondary (A-level)
                'keys'           => json_encode([
                    'thinking_skills',
                    'physical_education',
                    'work_education',
                ]),
                'grades_details' => json_encode([
                    'A' => 'Outstanding',
                    'B' => 'Above Average',
                    'C' => 'Needs Improvement',
                ]),
            ],
        ];

        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            $academicYear = AcademicYear::where([
                'school_id' => $school->id,
                'status'    => 1,
            ])->first();

            if (! $academicYear) {
                continue; // Skip if no active academic year
            }

            foreach ($gradingBands as $band) {
                DB::table('non_sc_grade')->updateOrInsert(
                    [
                        'school_id'        => $school->id,
                        'academic_year_id' => $academicYear->id,
                        'grade_name'       => $band['grade_name'],
                    ],
                    [
                        'keys'           => $band['keys'],
                        'grades_details' => $band['grades_details'],
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]
                );
            }
        }
    }
}