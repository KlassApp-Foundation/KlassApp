<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\AcademicTerm;
use App\Models\FeesCategories;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetStudentsMarks extends Controller
{
    //
    
    public function GetStudentMarks(User $learner, string $section){
        $admin = Auth::user();
        $schoolId = $admin->school_id;

         $learner = User::with([
                    'marks.subject',
                    'marks.remark',
                    'marks.exam',
                    'marks.student',
                    'marks.teacher',
                    'marks.school', 
                    'school',
                    'userprofile',
                   ])
                   ->whereHas('marks.exam', function ($query) use ($schoolId) {
                       $query->forSchool($schoolId);
                   })
                   ->where('id', $learner->id)
                   ->where('usergroup_id', 6)
                   ->first();
            $subjects = Subject::where("school_id", $schoolId)
                                 ->where("section_id", $section)
                                 ->get();
                           
            $class_name = Section::find($section)->name;
            $controls = ["SUBJECT", "OUT OF", "BOT", "MOT", "EOT", "AVG", "AGG", "REMARK", "TEACHER"];
            $grading_system = [
                "0-39"=>"F9",
                 "40-44"=>"P8",
                 "45-49"=>"P7",
                 "50-59"=>"C5",
                 "60-64"=>"C4",
                 "65-74"=>"C3",
                 "75-84"=>"D2",
                 "85-100"=>"D1"
                 ];
            // added to get fees
            $sectionId = $learner->marks->first()?->section_id;
            $fees = FeesCategories::where("school_id", $admin->school_id)
                                    ->where(function ($query) use($sectionId){
                                        $query->whereNull("section_id");
                                              if(!is_null($sectionId)){
                                                $query->orWhere("section_id", $sectionId);
                                              }
                                    }) 
                                ->sum("amount");
// added to get dates
            $now = now();                    
            $currentTerm = AcademicTerm::where("school_id", $schoolId)
                           ->where("starts_on", "<=", $now)
                           ->where("ends_on", ">=", $now)
                           ->orderBy("starts_on", "asc")
                           ->first();
            
            $nextTerm = AcademicTerm::where("school_id", $schoolId)
                        ->where("starts_on", ">", $currentTerm->ends_on)
                        ->orderBy("starts_on", "asc")
                        ->first();                       
    //  position
    // total students
    $learners = User::with(["studentAcademic.standardLink", "marks"])
                     ->where("school_id", $schoolId)
                     ->where("usergroup_id", 6)
                     ->whereHas("studentAcademic", function ($q) use($section){
                        $q->whereHas("standardLink", function ($q2) use($section){
                            $q2->where("section_id", $section);
                        });
                     });
                    
    $totalLearners = $learners->count();
         
        $learners = $learners->get();
        
        // totals
        $learners = $learners->map(function ($learner){
            $learner->total = $learner->marks->sum("marks");
            return $learner;
        });
        //   get position
        $learners = $learners->sortByDesc("total")->values();
        // dd($learners);
        $position = 1;
        $prevtotal = null;
        $learners = $learners->map(function ($student, $index) use(&$position, &$prevtotal){
            if($prevtotal !== null && $student->total < $prevtotal){
                $position = $index + 1;
            }
            $student->position = $position;
            $prevtotal = $student->total;
            return $student;
        });
        $myPos = $learners->where("id", $learner->id)->first()?->position;
            // $byStandard = $fees->where("standard_id", )
    return view("admin.marks.student", compact(
                "subjects", "learner", "controls", "class_name", "grading_system", "fees", "currentTerm", "nextTerm", "totalLearners", "myPos"
                ));
    }
    
}
