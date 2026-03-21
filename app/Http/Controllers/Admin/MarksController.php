<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;

use App\Models\AcademicYear;
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
$subjects = Subject::where("school_id", $schoolId)->get();

    return view('admin.marks.school-overview', compact('exams', "students", "studentCount", "subjects"));
}
    // get all student marks per class
    public function classExamOverview(Request $request)
{
    $student_usergroup_id = 6;
    $query = Marks::query()
        ->with(['student', 'subject', "remark", 'exam']) 
        ->whereHas("student", function($q) use($student_usergroup_id){
            $q->where("usergroup_id", $student_usergroup_id);
        })
        ->latest('created_at'); // or whatever default sort

    // Apply filters only if present
    if ($request->filled('term')) {
        // $query->where('exam.term', $request->term);
        $query->whereHas("exam", function($q) use($request){
            $q->where("term", $request->term);
        });
        $term = $request->term;
    }
    
    if ($request->filled('year')) {
        // $query->where('exam.academic_year_id', $request->year); // adjust column name
        $query->whereHas("exam", function($q) use($request){
            $q->where("academic_year_id", $request->year);
        });
        $year = AcademicYear::where("id", $request->year)->pluck("name")->first();
    }

    if ($request->filled('class')) {
        $query->whereHas('exam', function($q) use ($request) {
            $q->where('standard_id', $request->class); // or however your relation works
        });
        $class = Standard::find($request->class);
    }
    
    // Add more filters the same way (subject, student name search, min_marks, etc.)

    $marks = $query->paginate(15)->appends($request->query());
    // dd($marks);
    return view('admin.marks.filter', compact('marks', 'year', "term", "class"));
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
