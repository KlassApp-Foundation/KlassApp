<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\AcademicYear;

class ScholasticGradesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Modern Uganda scholastic grading configs (competence-based curriculum)
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
                DB::table('sc_grade')->updateOrInsert(
                    [
                        'school_id'        => $school->id,
                        'academic_year_id' => $academicYear->id,
                        'grade_name'       => $config['grade_name'],
                        'grading_method'   => $config['grading_method'],
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }
}