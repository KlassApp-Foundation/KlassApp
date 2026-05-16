<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\Section;
use App\Models\Subject;

class AcademicSetupService
{
    protected $gradingSystemService;
    public function __construct(GradingSystemService $gradingSystemService)
    {
        $this->gradingSystemService=$gradingSystemService;
    }
    public function defaultClassesAndSubjects($standard)
    {
        $school_id = $standard->school_id;
        $standardId = $standard->id;
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
            "o-level" => [
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
        "a-level" => [
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
            foreach ($classes as $class) {
                Subject::firstOrCreate([
                    "school_id"        => $school_id,
                    "academic_year_id" => $academicYear->id,
                    "standard_id"      => $standard->id,
                    "section_id"       => $class->id,
                    "name"             => $subjectName,
                    "code"             => $code,
                    "type"             => "core",
                    "status"           => 1
                ]);
            }
        }
// grading system for primary
 $primary_grades = [
    [ "grade"=>"D1", "points"=>1, "min_score"=>95, "max_score"=>100, "remark"=>"Excellent"],
    [ "grade"=>"D2", "points"=>2, "min_score"=>90, "max_score"=>94, "remark"=>"Very Good"],
    [ "grade"=>"C3", "points"=>3, "min_score"=>75, "max_score"=>89, "remark"=>"Good"],
    [ "grade"=>"C4", "points"=>4, "min_score"=>65, "max_score"=>74, "remark"=>"Fair"],
    [ "grade"=>"C5", "points"=>5, "min_score"=>60, "max_score"=>64, "remark"=>"Needs Effort"],
    [ "grade"=>"C6", "points"=>6, "min_score"=>50, "max_score"=>59, "remark"=>"Weak"],
    [ "grade"=>"P7", "points"=>7, "min_score"=>45, "max_score"=>49, "remark"=>"Poor"],
    [ "grade"=>"P8", "points"=>8, "min_score"=>40, "max_score"=>44, "remark"=>"Very Poor"],
    [ "grade"=>"F9", "points"=>9, "min_score"=>0,  "max_score"=>39, "remark"=>"Fail"],
];

// for secondary (O'level)
$olevel_grades = [
    [  "grade" => "A", "points" => 6, "min_score" => 80, "max_score" => 100, "remark" => "Exceptional" ],
    [  "grade" => "B", "points" => 5, "min_score" => 70, "max_score" => 79, "remark" => "Outstanding" ],
    [  "grade" => "C", "points" => 4, "min_score" => 60, "max_score" => 69, "remark" => "Satisfactory" ],
    [  "grade" => "D", "points" => 3, "min_score" => 50, "max_score" => 59, "remark" => "Basic" ],
    [  "grade" => "E", "points" => 2, "min_score" => 40, "max_score" => 49, "remark" => "Elementary" ],
    [  "grade" => "F", "points" => 0, "min_score" => 0,  "max_score" => 39, "remark" => "Fail" ],
];

$alevel_grades = [
    [  "grade" => "A", "points" => 6, "min_score" => 80, "max_score" => 100, "remark" => "Excellent" ],
    [  "grade" => "B", "points" => 5, "min_score" => 70, "max_score" => 79, "remark" => "Very Good" ],
    [  "grade" => "C", "points" => 4, "min_score" => 60, "max_score" => 69, "remark" => "Good" ],
    [  "grade" => "D", "points" => 3, "min_score" => 50, "max_score" => 59, "remark" => "Fair" ],
    [  "grade" => "E", "points" => 2, "min_score" => 40, "max_score" => 49, "remark" => "Pass" ],
    [  "grade" => "O", "points" => 1, "min_score" => 35, "max_score" => 39, "remark" => "Subsidiary Pass" ],
    [  "grade" => "F", "points" => 0, "min_score" => 0,  "max_score" => 34, "remark" => "Fail" ],
];

if($standardName === "primary"){
foreach ($primary_grades as $grade) {
        SchoolGradingSystem::create(["school_id" => $school_id, "standard_id"=>$standardId, ...$grade]);
    }
}elseif ($standardName === "o-level") {
    foreach ($olevel_grades as $grade) {
        SchoolGradingSystem::create(["school_id" => $school_id, "standard_id"=>$standardId, ...$grade]);
    }
}elseif($standardName === "a-level"){
    foreach ($alevel_grades as $grade) {
        SchoolGradingSystem::create(["school_id" => $school_id, "standard_id"=>$standardId, ...$grade]);
    }
}
        
    }
}