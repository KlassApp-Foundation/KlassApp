<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Seeder;

class ScholasticGradesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // The sc_grade.grading_method column is still the fork enum
        // cbse|passfail. Descriptive labels are kept on grade_name and
        // mapped onto those two stored values so MySQL accepts the row.
        // Lower Secondary uses letter grades A-E with descriptors
        // A-Level transitioning to simplified A-E
        $scholasticConfigs = [
            // Lower Secondary (S1–S4) - New CBC / NLSC system (main one now)
            [
                'grade_name'     => 'Lower Secondary (S1-S4)',
                'grading_method' => 'Achievement Levels',  // or 'Letter Grades (A-E)'
                // Optional: add 'details' column later with JSON descriptors
            ],

            // Upper Secondary (S5–S6) - Aligned/transitioning (2025+)
            [
                'grade_name'     => 'Upper Secondary (S5-S6)',
                'grading_method' => 'Principal Passes (A-E)',  // simplified from old system
            ],

            // Fallback / alternative methods used in some private/international schools
            [
                'grade_name'     => 'General / Private',
                'grading_method' => 'Letter Grades',
            ],

            [
                'grade_name'     => 'General / Supplementary',
                'grading_method' => 'Pass/Fail',
            ],
        ];

        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            $academicYear = AcademicYear::where([
                'school_id' => $school->id,
                'status'    => 1,
            ])->first();

            if (! $academicYear) {
                continue;
            }

            foreach ($scholasticConfigs as $config) {
                $method = $config['grading_method'] === 'Pass/Fail' ? 'passfail' : 'cbse';
                ReferenceUpsert::sync('sc_grade', [
                    'school_id' => $school->id,
                    'academic_year_id' => $academicYear->id,
                    'grade_name' => $config['grade_name'],
                ], [
                    'grading_method' => $method,
                ]);
            }
        }
    }
}