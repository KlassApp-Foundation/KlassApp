<?php
namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\Section;
use App\Models\Subject;

class AcademicSetupService{
    public function  defaultClassesAndSubjects($standard){
        $school_id = $standard->school_id;
        $academicYear = SiteHelper::getAcademicYear($school_id);
        $data=[
            "nursery" =>[
                "sections" => ['Baby Class',  'Middle Class','Top Class',],
                "subjects" => []
            ],
            "primary" =>[
                "sections" => [
                 'Primary One', 'Primary Two', 
                'Primary Three', 'Primary Four', 'Primary Five',   'Primary Six', 'Primary Seven'
                ],
                "subjects" => [
                    "English Language" => "013",
                    "Mathematics"      => "007",
                    "Social Studies"   => "015",
                    "Science"          => "010"
                    ]
            ],
            // to add paper based subjects for secondary
            "secondary" =>[
                "sections" => ['Senior One', 'Senior Two','Senior Three', 'Senior Four', 'Senior Five', 'Senior Six'],
                "subjects" => [
                     "English Language" => "112",
                     "General Mathematics"  => "456",
                     "Biology"          => "535",
                     "Chemistry"        => "534",
                     "Physics"          => "545",
                     "Geography"        => "273",
                     "History and Political Education" => "241"
              ]
            ]
        ];
        $standardName = strtolower($standard->name);
        if (!isset($data[$standardName])) return;

        // create sections
        $classes = [];
        foreach ($data[$standardName]["sections"] as $section){
            $classes[$section] = Section::firstOrCreate([
                "school_id" => $school_id,
                "name" => $section,
                "status" => 1
            ]);
        }  
// create subjects for primary and secondary
$subjects = $data[$standardName]["subjects"] ?? [];
            if(!empty($subjects)){
           foreach ($subjects as $subject => $code){ 
                foreach ($classes as $class){
                    Subject::firstOrCreate([
                         "school_id" => $school_id,
                         "academic_year_id" => $academicYear->id,
                         "standard_id" => $standard->id,
                         "section_id" => $class->id,
                         "name" => $subject,
                         "code" => $code ?? null,
                         "type" => "core",
                         "status" => 1
                     ]);
                }
           
        }
            }
    }
}