<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\AcademicTerm;
use App\Models\FeesCategories;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentReportHelperService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetStudentsMarks extends Controller
{
    //
    
    public function GetStudentMarks(StudentReportHelperService $studentHelper,  User $learner, string $section, Exam $exam){
        $admin = Auth::user();
        $schoolId = $admin->school_id;
        // learner
         $learner = $studentHelper->learner($schoolId, $learner, $exam);
        //  dd($exam);
                //    subjects
            $subjects = $studentHelper->subjects($schoolId, $section, $learner, $exam);
            // dd($subjects->first()->mark);
            $exams = $studentHelper->exam($schoolId, $exam);
            $controls = ["SUBJECT", "OUT OF"];
            $marksFromSubject = [];
            foreach ($exams as $ex){
                if(!in_array(strtoupper($ex->exam_type), $controls)){
                    $controls[] = strtoupper($ex->exam_type);
                    $marksFromSubject[] = $ex->subject;
                    // 
                }  
            }
            // dd($marksFromSubject);
             $next = [ "AVG", "AGG", "REMARK", "TEACHER"];
           $controls= array_merge($controls, $next);
        //    $student = $learner->first();
             $marks = $exam->marks->where("student_id", $learner->first()->id);
             $total = $marks->sum("marks");
             
             $examsDone = Exam::where("school_id", $schoolId)
                       ->where("section_id", $exam->section_id)
                       ->where("academic_term_id", $exam->academic_term_id)
                       ->where("academic_year_id", $exam->academic_year_id)
                       ->count();
            //  $xy = $marks->map(function($marks) use($examsDone){
            //     $total = $marks->marks->sum('marks');
            //     $marks->total = $total;
            //     $marks->average = $total / $examsDone;

            //     return $marks;
            //  });
            //  dd($xy);         

            $class_name = Section::find($section)->name;
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
            // dd($section);
            $fees = $studentHelper->fees($admin, $section);
// added to get dates
                             
    //  position
    // total students
    $learners = $studentHelper->totalStudentsInClass($schoolId, $section);
                    
    $totalLearners = $learners->count();
         
        $learners = $learners->get();
        
        // totals
        $learners = $studentHelper->totalMarks($learners);
        //   get position
        $learners = $learners->sortByDesc("total")->values();
        // dd($learners);
        // position
        $learners = $studentHelper->position($learners);

        $myPos = $learners->where("id", $learner->first()->id)->value("position");
        $learner = $learner->where("id", $learner->first()->id)->first();
            // $byStandard = $fees->where("standard_id", )
            // dd($learner);
    return view("admin.marks.student", compact(
                "subjects", "learner", "controls", "class_name", "grading_system", "fees", "currentTerm", "nextTerm", "totalLearners", "myPos", "exams", "marks", "examsDone", "marksFromSubject", "total"
                ));
    }
    
}
