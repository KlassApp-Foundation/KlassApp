<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Academics\Exam;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class DownloadStudentReport extends Controller
{
     public function download(User $learner, $section){
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
                 
            $pdf = Pdf::loadView("admin.marks.student-report", compact(
                "subjects", "learner", "controls", "class_name", "grading_system",
                ));     
            $pdf->setPaper("a4", "portrait");    
            $pdf->setOptions([
                "defaultFont" => "sans-serif",
                "isHtml5ParserEnabled" => true,
                "isRemoteEnabled" => true, //for external images
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
