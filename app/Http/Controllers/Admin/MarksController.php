<?php

namespace App\Http\Controllers\Admin;

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

    //
    // $marks = Marks::with(["subject", "remark", "exam", "student", "teacher", "school" ])
    // ->whereHas("exam", function ($query) use($schoolId) {
    //     $query->forSchool($schoolId);
    // })
    // ->whereHas("student", function($query){
    //     $query->students();
    // })
    // ->get();
$studentCount = $students->pluck('student_id')->unique()->count();
// to flter subjects depending on class
$subjects = Subject::where("school_id", $schoolId) ->where("standard_id", )->get();

    return view('admin.marks.school-overview', compact('exams', "students", "studentCount", "subjects"));
}
    // get all student marks per class
    public function classExamOverview(Request $request)
{
    $student_usergroup_id = 6;
    $schoolId = Auth::user()->school_id;
    // eagerload marks
    // $query = Marks::query()
    //     ->with(['student', 'subject', "remark", 'exam']) 
    //     ->whereHas("student", function($q) use($student_usergroup_id){
    //         $q->where("usergroup_id", $student_usergroup_id);
    //     })
    //     ->latest('created_at');
    //     $schoolId = Auth::user()->school_id;
        
    // ============ eagerload users ===========
  $query = User::query()
    ->with(['marks' => function ($q) use ($request) {
        $q->with('exam', 'subject', 'remark');

        // Filter marks only via exam relation
        // by term
        $q->when($request->filled('term'), function ($q2) use ($request) {
            $q2->whereHas('exam', fn($e) => $e
            ->where('academic_term_id', $request->term));
        });

        $q->when($request->filled('year'), function ($q2) use ($request) {
            $q2->whereHas('exam', fn($e) => $e->where('academic_year_id', $request->year));
        });

        $q->when($request->filled('standard'), function ($q2) use ($request) {
            $q2->whereHas('exam', fn($e) => $e->where('standard_id', $request->standard));
        });

        $q->when($request->filled('subject'), function ($q2) use ($request) {
            $q2->where('subject_id', $request->subject);
        });

        $q->when($request->filled("class"), function($q2) use($request){
            $q2->whereHas("exam", fn($e) =>$e ->where("section_id", $request->class));
        });

        $q->when($request->filled("examType"), function($q2) use($request){
            $q2->whereHas("exam", fn($e) =>$e->where("exam_type_id", $request->examType));
        });
    }])
    ->where('usergroup_id', 6)
    ->where('school_id', $schoolId)
    ->latest('created_at');
// dd($request->class);
        
    // Apply filters only if present
    // if ($request->filled('term')) {
    //     $query->whereHas("exam", function($q) use($request){
    //         $q->where("term", $request->term);
    //     });
    //     $term = $request->term;
    // }
    
    // if ($request->filled('year')) {
    //     $query->whereHas("exam", function($q) use($request){
    //         $q->where("academic_year_id", $request->year);
    //     });
    //     $year = AcademicYear::where("id", $request->year)->pluck("name")->first();
    // }

    // if ($request->filled('standard')) {
    //     $query->whereHas('exam', function($q) use ($request) {
    //         $q->where('standard_id', $request->standard); // or however your relation works
    //     });
    //     $class = Standard::find($request->standard);
    // }

    // if($request->filled("subject")){
    //     $query->where("subject_id", $request->subject);
    // }
    
    // Add more filters the same way (subject, student name search, min_marks, etc.)
    
    // dd($request->class);
    $marks = $query->paginate(15)->appends($request->query());
    $students = $query->paginate(15)->appends($request->query());
    // other filter options
    $years = AcademicYear::where('school_id', $schoolId)->get();
    $standards = Standard::where('school_id', $schoolId)->get();
    $classes = Section::where("school_id", $schoolId)->get();
    $subjects = Subject::where("school_id", $schoolId)->where("section_id", $request->class)->get();
    $examTypes = ExamType::all();
    $type = ExamType::find($request->examType);
    $terms = AcademicTerm::where("school_id", $schoolId)->get();
    $class = Section::find($request->class);
    // dd($subjects);
    // dd($marks);
    return view('admin.marks.filter', compact(
        'marks', 'year', "term", "class", "subjects", "years", "standards", "terms", "students", "classes", "examTypes", "type", "term"
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
