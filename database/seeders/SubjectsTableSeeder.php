<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\Standard;
use App\Models\Section;
use App\Models\AcademicYear;

class SubjectsTableSeeder extends Seeder
{
    public function run(): void
    {
        $coreSubjects = [
            // Pre-Primary / Nursery (play-based, very light academics)
            "Baby Class"     => ['English Rhymes & Stories', 'Numbers & Counting', 'Colour & Shapes', 'Creative Play', 'Religious & Moral Stories'],
            "Middle Class"   => ['English Language Basics', 'Numeracy', 'Environmental Awareness', 'Art & Craft', 'Physical Play & Health'],
            "Top Class"      => ['Pre-Literacy (English)', 'Pre-Numeracy', 'Social Habits', 'Creative Arts', 'Religious Education'],

            // Primary (P1–P7) — NCDC lower primary style
            "Primary 1"      => ['English Language', 'Mathematics', 'Literacy I', 'Numeracy I', 'Religious Education'],
            "Primary 2"      => ['English Language', 'Mathematics', 'Literacy II', 'Numeracy II', 'Religious Education'],
            "Primary 3"      => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education'],
            "Primary 4"      => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
            "Primary 5"      => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
            "Primary 6"      => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
            "Primary 7"      => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],

            // Lower Secondary (S1–S4) — new competence-based curriculum
            "Senior 1"       => ['English Language', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'History & Government', 'Geography', 'Religious Education'],
            "Senior 2"       => ['English Language', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'History & Government', 'Geography', 'Religious Education'],
            "Senior 3"       => ['English Language', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'History & Government', 'Geography', 'Religious Education'],
            "Senior 4"       => ['English Language', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'History & Government', 'Geography', 'Religious Education'],

            // Upper Secondary (S5–S6) — principal subjects (simplified, real combinations come later)
            "Senior 5"       => ['General Paper', 'Mathematics', 'Literature in English', 'Biology', 'Chemistry', 'Physics'],
            "Senior 6"       => ['General Paper', 'Mathematics', 'Literature in English', 'Biology', 'Chemistry', 'Physics'],
        ];

        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            $standards = Standard::where('school_id', $school->id)->get();
            $sections  = Section::where('school_id', $school->id)->get();

            foreach ($standards as $standard) {
                foreach ($sections as $section) {
                    $academic_year = AcademicYear::where([
                        ['school_id', $standard->school_id],
                        ['status', 1],
                    ])->first();

                    if (! $academic_year) continue;

                    // Get subjects for this standard name (fallback to empty if not found)
                    $subjects = $coreSubjects[$standard->name] ?? [];

                    foreach ($subjects as $subject) {
                        DB::table('subjects')->updateOrInsert(
                            [
                                'school_id'         => $standard->school_id,
                                'academic_year_id'  => $academic_year->id,
                                'standard_id'       => $standard->id,
                                'section_id'        => $section->id,
                                'name'              => $subject,
                            ],
                            [
                                'code'       => strtoupper(substr($subject, 0, 3)) . '-' . $section->name . '-' . $standard->name,
                                'type'       => 'core',
                                'status'     => '1',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }

                    // Optional language elective (Kiswahili / French / Local Language) for P4+ and secondary
                    if (preg_match('/(p[4-7]|primary\s*[4-7]|s[1-6]|senior\s*[1-6])/i', $standard->name)) {
                        $language = ['Kiswahili', 'French', 'Luganda'][rand(0, 2)]; // random demo

                        DB::table('subjects')->updateOrInsert(
                            [
                                'school_id'         => $standard->school_id,
                                'academic_year_id'  => $academic_year->id,
                                'standard_id'       => $standard->id,
                                'section_id'        => $section->id,
                                'name'              => $language,
                            ],
                            [
                                'code'       => 'EL-' . strtoupper(substr($language, 0, 3)) . '-' . $section->name . '-' . $standard->name,
                                'type'       => 'elective',
                                'status'     => '1',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            }
        }
    }
}