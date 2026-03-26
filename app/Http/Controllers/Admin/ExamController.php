<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\AcademicYear;
use App\Models\School;          // probably not needed if school_id from auth
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
   public function index(){

   $school_id = Auth::user()->school_id;
    $exam_types = DB::table("exam_types")->get();
    $subjects    = Subject::where('school_id', Auth::user()->school_id)->get();
    $standards   = Standard::where('school_id', Auth::user()->school_id)->get(); 
    $academicYears = AcademicYear::where('school_id', Auth::user()->school_id)->get();
    $sections = Section::where("school_id", $school_id)->get();
            
    $exams = Exam::where('school_id', Auth::user()->school_id)
        ->with(['standard', 'subject', 'academicYear', 'teacher', 'section'])
        ->latest()
        ->get();
         $teachers    = User::where('school_id', Auth::user()->school_id)
            ->whereIn('usergroup_id', [3, 5]) // adjust role names
            ->get();

    return view('admin.exams.index', compact(
        'exams', "exam_types", "subjects", "standards", "teachers", "sections", "academicYears"
        ));  
}

    public function list()
    {
        // $academicYears = AcademicYear::where('school_id', Auth::user()->school_id)
        //     ->get();
        $exams = Exam::with(['standard', 'subject', 'teacher', 'academicYear'])
        ->where('school_id', Auth::user()->school_id)
        ->latest()
        ->get();
        $exam_types = DB::table("exam_types")->get();
        $standards   = Standard::where('school_id', Auth::user()->school_id)->get(); // classes/grades
        $subjects    = Subject::where('school_id', Auth::user()->school_id)->get();
        $teachers    = User::where('school_id', Auth::user()->school_id)
            ->whereIn('usergroup_id', [3, 5]) // adjust role names
            ->get();
            

        return view('admin.exams.list', compact(
            "exams", 'standards', 'subjects', 'teachers',"exam_types", 
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'standard_id'      => 'required|exists:standards,id',
            'section_id'       => 'required|exists:sections,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term'             => 'required|in:1,2,3', // or string if named "Term I", etc.
            'subject_id'       => 'nullable|exists:subjects,id', // nullable if whole-class exam
            'teacher_id'       => 'nullable|exists:users,id',
            'exam_type_id'      => 'nullable|exists:exam_types,id', 
            'scheduled_at'     => 'nullable|date_format:Y-m-d\TH:i',
            'status'             => 'nullable|boolean'
        ]);

        // Auto-fill school_id from logged-in admin
        $validated['school_id'] = Auth::user()->school_id;
        Exam::create($validated);

        
        // In store()
    return redirect()->route('exams.list')
      ->with('successmessage', 'Exam created successfully!');
            
    }

    // To add edit/update/destroy later...

    public function edit(Exam $exam){

        $school_id = Auth::user()->school_id;
        $exam_types = ExamType::all();
        $subjects    = Subject::where('school_id', $school_id)->get();
        $standards   = Standard::where('school_id', $school_id)->get(); 
        $academicYears = AcademicYear::where('school_id', $school_id)->get();
        $sections = Section::where("school_id", $school_id)->get();
        $teachers = User::where('usergroup_id', 5)->where("school_id", $school_id)->get();

        return view("admin.exams.index", compact(
            "exam", "exam_types", "subjects", "standards", "academicYears", "sections", "teachers"
            ));
    }
    public function update(Request $request, Exam $exam){
        
        $validated = $request->validate([
            'standard_id'      => 'required|exists:standards,id',
            'section_id'       => 'required|exists:sections,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term'             => 'required|in:1,2,3', // or string if named "Term I", etc.
            'subject_id'       => 'nullable|exists:subjects,id', // nullable if whole-class exam
            'teacher_id'       => 'nullable|exists:users,id',
            'exam_type_id'      => 'nullable|exists:exam_types,id', 
            'scheduled_at'     => 'nullable|date_format:Y-m-d\TH:i',
            'status'             => 'nullable|in:done,postponed,undone'
        ]);
        $exam->update($validated);

        return redirect()->route("exams.list")->with("successmessage", "Exam updated successfully!");
    }

    public function archieve(Exam $exam){
        $school_id = Auth::user()->school_id;
        $exam->where("school_id", $school_id)->delete();
        return redirect()->route("exams.list")->with("successmessage", "Exam deleted successfully!");
    }
}