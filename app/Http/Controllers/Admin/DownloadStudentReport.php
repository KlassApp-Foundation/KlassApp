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
use App\Services\StudentReportCardService;
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
             $controls[] = StudentReportCardService::midExamControlColumnLabel($ex);
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

        $stdLink = StandardLink::where('school_id', $schoolId)
            ->where('section_id', $section)
            ->where('standard_id', $exam->standard_id)
            ->first();

        $pdfContent = \App\Http\Controllers\Admin\ReportCardsController::generatePdf(
            $learner->id, $exam, $stdLink, $schoolId, $studentHelper, new \App\Services\ReportCardCommentService, $totalLearners, $myPos
        );

        $firstName = $learner->userprofile->firstname ?? 'student';
        $lastName = $learner->userprofile->lastname ?? 'unknown';
        $filename = str_replace(' ', '_', "{$firstName}_{$lastName}") . '_report_card.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);

    }
    
}
