<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Academics\Exam;
use App\Models\AcademicTerm;
use App\Models\FeesCategories;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentReportHelperService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class DownloadStudentReport extends Controller
{
     public function download(StudentReportHelperService $studentHelper,User $learner, $section, Exam $exam){
        $admin = Auth::user();
        $schoolId = $admin->school_id;

         $learner = $studentHelper->learner($schoolId, $learner, $exam);
         $subjects = $studentHelper->subjects($schoolId, $section, $learner, $exam);

         $exams = $studentHelper->exam($schoolId, $exam);
            $controls = ["SUBJECT", "OUT OF"];
             $cals = ["AVG"];
             $uniqueExamTypes = $exams->pluck('examType')->unique()->count();
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
            // dd($marksFromSubject);
             $next = ["DIVISION", "TEACHER", "REMARK"];
           $controls= array_merge($controls, $next);

           $marks = $exam->marks->where("student_id", $learner->id);
            $total = $learner->marks->sum("marks");
            // dd($total);
            $examsDone = $studentHelper->examsDone($schoolId, $exam);
            $grade = $studentHelper->grade($learner, $exam);

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
            $fees = $studentHelper->fees($admin, $section);  
             
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
        $nextTerm = AcademicTerm::where("school_id", $schoolId)->where("starts_on", ">", now())->first();

        $myPos = $learners->where("id", $learner->id)->value("position");
        $learner = $learner->where("id", $learner->id)->first();
        $pdf = Pdf::loadView("admin.marks.student-report", compact(
            "subjects", "learner", "controls", "class_name", "grading_system", "fees", "nextTerm", "totalLearners", "myPos", "exams", "marks", "examsDone", "marksFromSubject", "total", "uniqueExamTypes", "grade"
            ));     
        $pdf->setPaper("a4", "portrait");    
        $pdf->setOptions([
            "defaultFont" => "sans-serif",
            "isHtml5ParserEnabled" => true,
            "isRemoteEnabled" => true, //for external images
            "isPhpEnabled" => true,
            "isJavascriptEnabled" => true,
            "tempDir" =>storage_path("app/dompdf"),
            "fontDir" =>storage_path("app/dompdf/fonts"),
                "fontCache" =>storage_path("app/dompdf/fonts")

            ]);
            // create temp foler if it's nuh there
            if(!is_dir(storage_path("app/dompdf/fonts"))){
                mkdir(storage_path("app/dompdf/fonts"), 0775, true);
            }
            if (!$learner) {
          abort(404, "Student not found or has no marks.");
             }
            $first_name = $learner->userprofile->firstname ?? "student";
            $last_name = $learner->userprofile->lastname ?? "unknown";
            
            $filename = str_replace(
                " ", "_", $first_name . "_" . $last_name
                ) . "_report_card.pdf";
            return $pdf->download("$filename");    

    }
    
}
