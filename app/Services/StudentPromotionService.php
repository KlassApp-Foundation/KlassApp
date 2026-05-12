<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\AcademicYear;
use App\Models\Promotion;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentPromotionRules;
use App\Models\Subject;
use App\Models\User;

class StudentPromotionService
{
    public function promoteToNextClass($avg, $exam, $schoolId, $user){
        $nextAcademicYearId = AcademicYear::where("school_id", $schoolId)->where("description", "Upcoming Academic Year")->value("id");
        $standardLinkId = StandardLink::where("school_id", $schoolId)->where("section_id", $exam->section_id)->value("standard_id");
        $sections = Section::where("school_id", $schoolId)->orderByDesc("id")->get();
        // dd($sections);
        $rules = StudentPromotionRules::where("school_id", $schoolId)
                 ->where("section_id", $exam->section_id)
                 ->where("min_average", ">=", $avg)
                  ->first();
                //   dd($rules);
       if($rules){
        // next section (class) id
        foreach ($sections as $section) {
            if($section->id === $exam->section_id){
             Promotion::create([
            'school_id' => $schoolId,
            'user_id' => $user->id, 
            'current_academic_year_id' => $exam->academic_year_id,
            'current_standard_id' => $standardLinkId,
            'current_section_id' => $section->id,
            'exam_id' => $exam->id,
            'next_academic_year_id' => $nextAcademicYearId,
            'next_standard_id' => $standardLinkId,
            'next_section_id' => "",
            'comments' => "Promoted",
            'status' => 1,
        ]);            }
           
        }
        
       }       
        return $rules;
    }

}