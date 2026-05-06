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
    
    public function GetStudentMarks(StudentReportHelperService $studentHelper,  User $user, string $section, Exam $exam){
        $admin = Auth::user();
        $schoolId = $admin->school_id;
        // learner
         $learner = $studentHelper->learner($schoolId, $user, $exam);
        //  dd($learner);
                //    subjects
            $subjects = $studentHelper->subjects($schoolId, $section, $learner, $exam);
            $grade = $studentHelper->grade($learner, $exam);

            $exams = $studentHelper->exam($schoolId, $exam);

            // dd($grade);
            $controls = ["SUBJECT", "OUT OF"];
            // many exam types
            $uniqueExamTypes = $exams->pluck('examType')->unique()->count();
            $cals = ["AVG"];
            $marksFromSubject = [];
            foreach ($exams as $ex){
                if(!in_array(strtoupper($ex->examType->code), $controls)){
                    $controls[] = strtoupper($ex->examType->first()->code);
                    $marksFromSubject[] = $ex->subject;
                    // check for more exam types to add average
                    if($uniqueExamTypes > 1){
                        $controls= array_merge($controls, $cals);
                    }
                }
                
            }
            // dd($controls);
             $next = ["DIVISION", "TEACHER", "REMARK"];
           $controls= array_merge($controls, $next);
        //    student's total marks
        $studentTotals = $studentHelper->learnersTotal($exam, $user);
             $marks = $exam->marks->where("student_id", $learner->id);
             
             $examsDone = $studentHelper->examsDone($schoolId, $exam);     

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
            
            $fees = $studentHelper->fees($admin, $section);
                             
    //  position
    // total students
    $learners = $studentHelper->totalStudentsInClass($schoolId, $section);
                    
    $totalLearners = $learners->count();
    $learners = $learners->get();
        
        // totals
        $learners = $studentHelper->totalMarks($learners);
        $total = $learners->find($user->id)->total;
        //   get position
        $learners = $learners->sortByDesc("total")->values();
        // dd($learners);
        // position
        $learners = $studentHelper->position($learners);

        $myPos = $learners->where("id", $learner->id)->value("position");
        $learner = $learner->where("id", $learner->id)->first();
        $nextTerm = AcademicTerm::where("school_id", $schoolId)->where("starts_on", ">", now())->first();
        
            // $byStandard = $fees->where("standard_id", )
            // dd($nextTerm);
    return view("admin.marks.student", compact(
                "subjects", "learner", "controls", "class_name", "grading_system", "fees", "nextTerm", "totalLearners", "myPos", "exams", "marks", "examsDone", "marksFromSubject", "total", "studentTotals", "uniqueExamTypes", "grade"
                ));
    }
    
}
