<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\AcademicYear;
use App\Models\Promotion;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentPromotionRules;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentPromotionService
{
    // promote class students
     public function promoteStudents(Collection $students, string $schoolId, string $sectionId, int $examsDone){
        
        $currentStandardId = StandardLink::where("school_id", $schoolId)->where("section_id", $sectionId)->value("standard_id");
        
        $nextSection = Section::where("school_id", $schoolId)
                         ->where("id", ">", $sectionId)
                         ->orderBy("id")
                         ->first();
       $nextStandard = Standard::where("school_id", $schoolId)
                         ->where("id", ">", $currentStandardId)
                         ->orderBy("id")
                         ->first();            
       $nextAcademicYearId = AcademicYear::where("school_id", $schoolId)->where("description", "Upcoming Academic Year")->value("id");   
       $promotions = Promotion::where("school_id", $schoolId)->get();

        // loop through students
        $promotion = null;
        foreach ($students as $student){
            $learner = StudentAcademic::where("school_id", $schoolId)->where("user_id", $student->id)->first();
            foreach ($promotions as $promo){
                if($promo->next_section_id === $learner->section_id){
                    continue;
                }
            }
            $points = 0;
            foreach ($student->marks as $mark){
                $pts = SchoolGradingSystem::where('school_id', $schoolId)
                ->where('standard_id', $mark->exam->standard_id)
                ->where('grade', $mark->grade)
                ->value("points");
                $points += $pts;
            }     

            $avg = $student->marks->sum("marks") / $examsDone;
            // dd($avg); 
            $rules = StudentPromotionRules::where("school_id", $schoolId)
                    ->where("section_id", $sectionId)
                    ->where(function ($query) use ($avg, $points) {
                        // by average
                        $query->where(function ($q) use ($avg) {
                            $q->where("rule_type", "average")
                              ->where("min_average", "<=", $avg);
                        })
                        // by aggregates
                        ->orWhere(function ($q) use ($points) {
                            $q->where("rule_type", "aggregate")
                              ->where("min_aggregate", ">=", $points);
                        })
                        // by points
                        ->orWhere(function ($q) use ($points) {
                            $q->where("rule_type", "points")
                              ->where("min_points", "<=", $points);
                        });
                    })
                    ->first();

            if($rules){

            $exam = $student->marks->first()->exam; //to be removed
           $promotion = Promotion::updateOrCreate(
                        [
                            'school_id' => $schoolId,
                            'user_id' => $student->id,
                            'current_academic_year_id' => $exam->academic_year_id,
                            'current_standard_id' => $currentStandardId,
                            'current_section_id' => $exam->section_id,
                            'exam_id' => $exam->id,
                        ],
                        [
                            'next_academic_year_id' => $nextAcademicYearId,
                            'next_standard_id' => $nextStandard?->id ?? $currentStandardId,
                            'next_section_id' => $nextSection?->id ?? null,
                            'comments' => "Promoted to {$nextSection->name}",
                            'status' => 1,
                        ]
                     );

        $promotion = Promotion::where("id", $promotion->id)->value("comments");
        }   
        }
        return $promotion;
    }

    // promotion finalization
    public function finalizePromotions(string $schoolId, string $sectionId)
{
    $promotions = Promotion::where('school_id', $schoolId)
        ->where('current_section_id', $sectionId)
        ->where('status', 1)
        ->get();
$promote =  "";
    foreach ($promotions as $promotion) {
        $promote = $promotion->comments;
        $student = StudentAcademic::where(
            'user_id',
            $promotion->user_id
        )->first();

        $nextStandardLink = StandardLink::where([
            'school_id' => $schoolId,
            'standard_id' => $promotion->next_standard_id,
            'section_id' => $promotion->next_section_id,
        ])->first();

        if ($student && $nextStandardLink) {
            $student->standardLink_id = $nextStandardLink->id;
            $student->academic_year_id = $promotion->next_academic_year_id;
            $student->save();
        }
        
    }
    return $promote;
}

}