<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Academics\Exam;
use App\Models\Academics\NurseryAssessment;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\AcademicTerm;
use App\Models\FeesCategories;
use App\Models\Promotion;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentReportHelperService;
use App\Services\ReportCardCommentService;
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

         $midExams = $exams->filter(fn($e) => $e->examType->code === 'MID')->sortBy('scheduled_at')->values();
         $eotExams = $exams->filter(fn($e) => $e->examType->code !== 'MID')->values();
         $allExamColumns = $midExams->merge($eotExams);

         $controls = ["SUBJECT", "OUT OF"];
         foreach ($midExams as $ex) {
             $controls[] = strtoupper($ex->scheduled_at->format('M')) . ' MID';
         }
         foreach ($eotExams as $ex) {
             $controls[] = 'EOT';
         }
         $controls = array_merge($controls, ['DIVISION', 'TEACHER', 'REMARK']);

           // Detect nursery level BEFORE marks calculations
           $isNursery = false;
           $nurseryAssessments = collect();
           $standard = $learner->studentAcademicLatest?->standardLink?->standard;
           if ($standard) {
               $levelType = \App\Helpers\GradingHelper::levelTypeForStandard($standard);
               $isNursery = ($levelType === 'nursery');
               if ($isNursery) {
                   $nurseryAssessments = NurseryAssessment::where('student_id', $learner->id)
                       ->where('academic_term_id', $exam->academic_term_id)
                       ->get()
                       ->keyBy('domain');
               }
           }

           // Marks calculations — skip for nursery (no numeric marks)
           $marks = collect();
           $total = 0;
           $examsDone = 0;
           $grade = null;

            if (!$isNursery) {
                $marks = $exam->marks->where("student_id", $learner->id);
                $total = $learner->marks
                    ? $learner->marks->filter(fn($m) => $m->exam?->examType?->contributes_to_report_total)->sum("marks")
                    : 0;
                $examsDone = $studentHelper->examsDone($schoolId, $exam);
                $grade = $studentHelper->grade($learner, $exam);
                $teacherComment = $standard
                    ? (new ReportCardCommentService)->commentFor((int) $total, $standard->name, $learner->id, $exam->id)
                    : '';
           }

            // promotion
             $promotion = Promotion::where("school_id", $schoolId)
                              ->where("user_id", $learner->id)
                              ->where("current_section_id", $exam->section_id)
                              ->value("comments");
            // dd($promotion);                  
            $class_name = Section::find($section)->name;
            $grading_system = SchoolGradingSystem::where("school_id", $schoolId)->get();
            
            // added to get fees
            $fees = $studentHelper->fees($admin, $section);  
             
            //  position
     // total students
    $learners = $studentHelper->totalStudentsInClass($schoolId, $section);
                    
    $totalLearners = $learners->count();
    $learners = $learners->get();        
    // totals + position (aggregate-aware: uses grade points when available, total marks otherwise)
    $learners = $studentHelper->position($learners, $exam);
        $nextTerm = AcademicTerm::where("school_id", $schoolId)->where("starts_on", ">", now())->first();
        $school = Auth::user()->school;
        
        $myPos = $learners->where("id", $learner->id)->value("position");
        // $learner is already a valid User model from route binding — no need to re-query
        $pdf = Pdf::loadView("admin.marks.student-report", compact(
            "subjects", "learner", "controls", "class_name", "grading_system", "fees", "nextTerm", "totalLearners", "myPos", "total", "grade", "school", "isNursery", "nurseryAssessments", "teacherComment",
            "allExamColumns", "midCount", "eotCount"
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
          return redirect()->back()->with('error', 'This student has no marks recorded for this exam yet.');
             }
            $first_name = $learner->userprofile->firstname ?? "student";
            $last_name = $learner->userprofile->lastname ?? "unknown";
            
            $filename = str_replace(
                " ", "_", $first_name . "_" . $last_name
                ) . "_report_card.pdf";
            return $pdf->download("$filename");    

    }
    
}
