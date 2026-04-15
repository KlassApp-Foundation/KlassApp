<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\Section;
use App\Models\Subject;

class AcademicSetupService
{
    public function defaultClassesAndSubjects($standard)
    {
        $school_id = $standard->school_id;
        $academicYear = SiteHelper::getAcademicYear($school_id);

        $data = [
            "nursery" => [
                "sections" => ['Baby Class', 'Middle Class', 'Top Class'],
                "subjects" => []
            ],
            "primary" => [
                "sections" => [
                    'Primary One', 'Primary Two', 'Primary Three', 'Primary Four',
                    'Primary Five', 'Primary Six', 'Primary Seven'
                ],
                "subjects" => [
                    "English Language" => "013",
                    "Mathematics"      => "007",
                    "Social Studies"   => "015",
                    "Science"          => "010"
                ]
            ],
            "O-Level" => [
                "sections" => ['Senior One', 'Senior Two', 'Senior Three', 'Senior Four'],
                "subjects" => [
                    "English Language"                => "112",
                    "General Mathematics"             => "456",
                    "Biology"                         => "535",
                    "Chemistry"                       => "534",
                    "Physics"                         => "545",
                    "Geography"                       => "273",
                    "History and Political Education" => "241"
                ]
            ],
        "A-Level" => [
            "sections" => ['Senior Five', 'Senior Six'],
            "subjects" => [
                "General Paper"                  => "800",
                "ICT"                            => "840",
                "General Mathematics"            => "840",
                "Biology"                        => "530",
                "Chemistry"                      => "540",
                "Physics"                        => "550",
                "Geography"                      => "220",
                "History and Political Education"=> "210",
                "Literature in English"          => "230",
                "Economics"                      => "220",
                "Entrepreneurship Education"     => "840",
                "Christian Religious Education"  => "290",
                "Islamic Religious Education"    => "291",
                "Art & Design"                   => "610",
                "Music"                          => "620",
                "Agriculture"                    => "510"
            ]
          ]
        ];

        $standardName = strtolower(trim($standard->name ?? ''));

        if (!isset($data[$standardName])) {
            return; // or log a warning
        }

        // Create sections
        $classes = [];
        foreach ($data[$standardName]["sections"] as $sectionName) {
            $classes[] = Section::firstOrCreate([
                "school_id" => $school_id,
                "name"      => $sectionName,
                "status"    => 1,
            ]);
        }

        // Create subjects (for primary & secondary)
        $subjects = $data[$standardName]["subjects"] ?? [];

        foreach ($subjects as $subjectName => $code) {
            // foreach ($classes as $class) {
                Subject::firstOrCreate([
                    "school_id"        => $school_id,
                    "academic_year_id" => $academicYear->id,
                    "standard_id"      => $standard->id,
                    // "section_id"       => $class->id,
                    "name"             => $subjectName,
                    "code"             => $code,
                    "type"             => "core",
                    "status"           => 1
                ]);
            // }
        }
    }
}