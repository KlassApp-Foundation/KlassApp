<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\AcademicTerm;
use App\Models\FeesCategories;
use App\Models\Promotion;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentPromotionService;
use App\Services\StudentReportHelperService;
use App\Services\StudentReportCardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetStudentsMarks extends Controller
{
    //
    
    public function GetStudentMarks(StudentReportHelperService $studentHelper,  User $user, string $section, Exam $exam, StudentPromotionService $studentPromotion){
        $admin = Auth::user();
        $schoolId = $admin->school_id;
        // learner
         $learner = $studentHelper->learner($schoolId, $user, $exam);
        //  dd($learner);
                //    subjects
            $subjects = $studentHelper->subjects($schoolId, $section, $learner, $exam);
            $grade = $studentHelper->grade($learner, $exam);

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
        //    dd($controls);
        //    student's total marks
        $studentTotals = $studentHelper->learnersTotal($exam, $user);
             $marks = $exam->marks->where("student_id", $learner->id);
             
             $examsDone = $studentHelper->examsDone($schoolId, $exam);     
            $class_name = Section::find($section)->name;
            $grading_system = SchoolGradingSystem::where("school_id", $schoolId)->get();
            
            $fees = $studentHelper->fees($admin, $section);
                             
    //  position
    // total students
    $learners = $studentHelper->totalStudentsInClass($schoolId, $section);
                    
    $totalLearners = $learners->count();
    $learners = $learners->get();
        
        $promotion = Promotion::where("school_id", $schoolId)
                              ->where("user_id", $user->id)
                              ->value("comments");

        // totals + position (aggregate-aware)
        $learners = $studentHelper->position($learners, $exam);
        $total = $learners->find($user->id)->total;

        $myPos = $learners->where("id", $learner->id)->value("position");
        $learner = $learner->where("id", $learner->id)->first();
        $nextTerm = AcademicTerm::where("school_id", $schoolId)->where("starts_on", ">", now())->first();
         $school = Auth::user()->school;
            // $byStandard = $fees->where("standard_id", )
            // dd($nextTerm);
    return view("admin.marks.student", compact(
                "subjects", "learner", "controls", "class_name", "grading_system", "fees", "nextTerm", "totalLearners", "myPos", "total", "grade", "school",
                "allExamColumns", "midCount", "eotCount"
                ));
    }
    
}
