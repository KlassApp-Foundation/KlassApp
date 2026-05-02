<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class MarksController extends Controller
{


// all marks according to exam and standard
 public function schoolMarksOverview()
{
    $schoolId = Auth::user()->school_id;

   $students = User::with(
    ["marks.subject", "marks.remark", "marks.exam", "marks.student", "marks.teacher", "marks.school" ])
            ->whereHas("marks.exam", function ($query) use($schoolId){
                $query->forSchool($schoolId);
            })
            ->where("usergroup_id", 6)
            ->get();

$studentCount = $students->pluck('student_id')->unique()->count();
// to flter subjects depending on class
$subjects = Subject::where("school_id", $schoolId) ->where("standard_id", )->get();

    return view('admin.marks.school-overview', compact('exams', "students", "studentCount", "subjects"));
}
    // get all student marks per class
    public function classExamOverview(Request $request)
{
    $schoolId = Auth::user()->school_id;
    $academic_year_id = AcademicYear::where("school_id", $schoolId)->where("description", "Current Academic Year")->value("id");
    
    // ============ eagerload users ===========
  $query = User::query()
    ->with(['marks' => function ($q) use ($request) {
        $q->with('exam', 'subject', 'remark')
         ->whereHas("exam", function($e) use($request){
            $e->where("status", "submitted");
        });
              // by term
        $q->when($request->filled('term'), function ($q2) use ($request) {
            $q2->whereHas('exam', fn($e) => $e
            ->where('academic_term_id', $request->term));
        });

        $q->when($request->filled('year'), function ($q2) use ($request) {
            $q2->whereHas('exam', fn($e) => $e->where('academic_year_id', $request->year));
        });

         $q->when($request->filled('subject'), function ($q2) use ($request) {
            $q2->where('subject_id', $request->subject);
        });

        $q->when($request->filled("class"), function($q2) use($request){
            // $q2->whereHas("")
            $q2->whereHas("exam", fn($e) =>$e ->where("section_id", $request->class));
            
        });

        // $q->when($request->filled('standard'), function ($q2) use ($request) {
        //     $q2->whereHas('exam', fn($e) => $e->where('standard_id', $request->standard));
        // });
    }, "studentAcademic.standardLink"])
    ->where('usergroup_id', 6)
    ->where('school_id', $schoolId)
    ->whereHas("studentAcademic", function ($query) use($request){
        $query->whereHas("standardLink", function($q2) use($request){
            $q2->where("section_id", $request->class);
        });
    })
    ->latest('created_at');
    $marks = $query->paginate(15)->appends($request->query());

    // $students = $query->paginate(15)->appends($request->query());
    // get total exams done (subjects covered)
    $examsDone = Exam::where("school_id", $schoolId)
          ->where("section_id", $request->class)
          ->where("academic_term_id", $request->term)
          ->where("academic_year_id", $academic_year_id)
          ->count();
        $students = $query->get();
           // calculate total
           $students = $students->map(function ($student) use($examsDone) {
            $total = $student->marks->sum('marks');
           $student->total = $total;
           $student->average = $total / $examsDone;
           return $student;
       });
       $students = $students->sortByDesc("total")->values();
       // position with tie support
       $position = 1;
       $prevTotal = null;
       $students = $students->map(function($student, $index) use (&$position, &$prevTotal){
        if ($prevTotal !== null && $student->total < $prevTotal){
            $position = $index + 1;
        }
        $student->position = $position;
        $prevTotal = $student->total;

        return $student;
       });
    //    paginate
    $page = request()->get("page", 1);
    $perpage = 15;
    $students = new LengthAwarePaginator(
        $students->forPage($page, $perpage),
        $students->count(),
        $perpage,
        $page,
        ["path" => request()->url(), "query" => request()->query()]
    );
    //    dd($position);
    // other filter options
    $years = AcademicYear::where('school_id', $schoolId)->get();
    $standards = Standard::where('school_id', $schoolId)->get();
    $classes = Section::where("school_id", $schoolId)->get();
    $subjects = Subject::where("school_id", $schoolId)->where("section_id", $request->class)->get();
   
    $subjectsCovered = Exam::where("school_id", $schoolId)
                       ->where("section_id", $request->class)
                       ->where("academic_term_id", $request->term)
                       ->distinct("subject_id")
                       ->count("subject_id");

    $examTypes = ExamType::all();
    $type = ExamType::find($request->examType);
    $terms = AcademicTerm::where("school_id", $schoolId)->get();
    $class = Section::where("id", $request->class)->first();
    $exam = Exam::where("school_id", $schoolId)
           ->where("academic_year_id", $academic_year_id)
           ->where("academic_term_id", $request->term)->first();
       $headers = ['Total', 'Average', 'Grade', 'Position', 'Actions'];    
    //    dd($exam);
    return view('admin.marks.filter', compact(
        'marks', 'year', "term", "class", "subjects", "years", "standards", "terms", "students", "classes", "examTypes",
         "type", "term", "subjectsCovered", "headers", "exam"
        ));
}

// private function to compute grade
private function computeGrade($average)
{
    if ($average >= 80) return 'A';
    if ($average >= 70) return 'B';
    if ($average >= 60) return 'C';
    if ($average >= 50) return 'D';
    return 'E';
}
}
