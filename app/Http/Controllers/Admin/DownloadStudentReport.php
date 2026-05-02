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

           $marks = $exam->marks->where("student_id", $learner->first()->id);
            $total = $marks->sum("marks");
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

        $myPos = $learners->where("id", $learner->first()->id)->value("position");
        $learner = $learner->where("id", $learner->first()->id)->first();
    //    dd($exam);
        $pdf = Pdf::loadView("admin.marks.student-report", compact(
            "subjects", "learner", "controls", "class_name", "grading_system", "fees", "currentTerm", "nextTerm", "totalLearners", "myPos", "exams", "marks", "examsDone", "marksFromSubject", "total"
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
            // dd($learner);
            $filename = str_replace(
                " ", "_", $first_name . "_" . $last_name
                ) . "report_card.pdf";

            return $pdf->download("$filename");    

    }
    
}
