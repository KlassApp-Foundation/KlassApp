<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\AcademicYear;
use App\Models\Promotion;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentPromotionRules;
use App\Models\Subject;
use App\Models\User;

class StudentPromotionService
{
    public function promoteToNextClass($avg, $exam, $schoolId, $user){
        $nextAcademicYearId = AcademicYear::where("school_id", $schoolId)->where("description", "Upcoming Academic Year")->value("id");
        $currentStandardId = StandardLink::where("school_id", $schoolId)->where("section_id", $exam->section_id)->value("standard_id");
        $nextSection = Section::where("school_id", $schoolId)
                         ->where("id", ">", $exam->section_id)
                         ->orderBy("id")
                         ->first();
       $nextStandard = Standard::where("school_id", $schoolId)
                         ->where("id", ">", $currentStandardId)
                         ->orderBy("id")
                         ->first();                  
        $rules = StudentPromotionRules::where("school_id", $schoolId)
                 ->where("section_id", $exam->section_id)
                 ->where("min_average", "<=", $avg)
                  ->first();
                //   dd($rules);
        $student = StudentAcademic::where("school_id", $schoolId)->where("user_id", $user->id)->first();
        
       if($rules){
             Promotion::create([
            'school_id' => $schoolId,
            'user_id' => $user->id, 
            'current_academic_year_id' => $exam->academic_year_id,
            'current_standard_id' => $currentStandardId,
            'current_section_id' => $exam->section_id,
            'exam_id' => $exam->id,
            'next_academic_year_id' => $nextAcademicYearId,
            'next_standard_id' => $nextStandard?->id ?? $currentStandardId,
            'next_section_id' => $nextSection?->id ?? null,
            'comments' => "Promoted to $nextSection->name",
            'status' => 1,
        ]);

        $nextStandardLink = StandardLink::where([
            ['school_id', $schoolId],
            ['standard_id', $nextStandard->id ?? $currentStandardId],
            ['section_id', $nextSection->id],
      ])->first();
      $student->standardLink_id = $nextStandardLink->id;
      $student->save();


           
        }
    return $rules;
    }

}