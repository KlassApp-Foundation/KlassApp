<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Services\MarksReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Academics\Exam;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use Barryvdh\DomPDF\Facade\Pdf;

class DownloadMarksSheet extends Controller
{
    //
    public function download(MarksReportService $service, Request $request){
            $schoolId = Auth::user()->school_id;
           $academic_year = AcademicYear::where("school_id", $schoolId)->where("description", "Current Academic Year")->first();

            $examsDone = Exam::where("school_id", $schoolId)
                       ->where("section_id", $request->class)
                       ->where("academic_term_id", $request->term)
                       ->where("academic_year_id", $academic_year->id)
                       ->count();
        // get marks  
        $students = $service->getMarks($request, $schoolId);
        // build marks
       $students = $service->buildMarksheet($students, $examsDone);
       $subjects = Subject::where("school_id", $schoolId)->where("section_id", $request->class)->get();
       $term = AcademicTerm::where("school_id", $schoolId)->where("id", $request->term)->value("name");
       $headers = ['Total', 'Average', 'Grade', 'Position'];    

        // 3. generate PDF
        // dd($subjects);
    $pdf = Pdf::loadView('admin.marks.marksheet', [
        'students' => $students,
        'class' => $request->class,
        'term' => $term,
        'academic_year' => $academic_year,
        "subjects" => $subjects,
        "headers" => $headers
    ]);
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
     return $pdf->download(
    'marksheet_for_'.$request->class.'_term_'.$request->term.'.pdf'
);

    }
}
