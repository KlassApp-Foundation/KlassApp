<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateExamRequest;
use App\Http\Requests\UpdateExamsRequest;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\AcademicTerm;
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

// fetch class to link subject on exam creation
public function sections(){
    $school_id = Auth::user()->school_id;
    $classes = Section::where("school_id", $school_id)->get();
    return view("admin.exams.create", compact("classes"));
}

   public function create(Request $request){

   $school_id = Auth::user()->school_id;
    
    // $standards   = Standard::where('school_id', $school_id)->get(); 
    $sections = Section::where("school_id", $school_id)->orderByDesc("id")->get();
    $academicYears = AcademicYear::where('school_id', $school_id)->get();
    $selectedClassId = $request->get("section");
    if($selectedClassId){
        $subjects = Subject::where('section_id', $selectedClassId) ->where("school_id", $school_id)->get();
    }
    $terms = AcademicTerm::where("school_id", $school_id)->get();
    $exams = Exam::where('school_id', $school_id)
        ->with(['standard', 'subject', 'academicYear', 'teacher', 'section'])
        ->latest()
        ->get();
         $teachers    = User::where('school_id', $school_id)
            ->where('usergroup_id', 5) 
            ->get();
    $examTypes =  ExamType::all();
    return view('admin.exams.create', compact(
        'exams', "teachers", "sections", "academicYears", "terms", "subjects", "examTypes"
        ));  
}

    public function index()
    {
        // $academicYears = AcademicYear::where('school_id', Auth::user()->school_id)
        //     ->get();
        $exams = Exam::with(['standard', 'subject', 'teacher', 'academicYear'])
        ->where('school_id', Auth::user()->school_id)
        ->latest()
        ->get();
        // $exam_types = DB::table("exam_types")->get();
        $standards   = Standard::where('school_id', Auth::user()->school_id)->get(); // classes/grades
        $subjects    = Subject::where('school_id', Auth::user()->school_id)->get();
        $teachers    = User::where('school_id', Auth::user()->school_id)
            ->whereIn('usergroup_id', [3, 5])
            ->get();
            
       $headers = [
        "No", "Term", "Type", "Status", "Level", "Class", "Subject", "Teacher", "Actions"
        ];     
        return view('admin.exams.index', compact(
            "exams", 'standards', 'subjects', 'teachers', "headers"
        ));
    }

    public function store(CreateExamRequest $request)
    {
        // dd($request);
        $validated = $request->validated();
        // dd($validated);
        Exam::create($validated);
    return redirect()->route('admin.exams')
      ->with('successmessage', 'Exam created successfully!');
            
    }

    // To add edit/update/destroy later...

    public function edit(Exam $exam){

        $school_id = Auth::user()->school_id;
        // $exam_types = ExamType::all();
        $subjects    = Subject::where('school_id', $school_id)->where("section_id", $exam->section_id)->get();
        $standards   = Standard::where('school_id', $school_id)->get(); 
        $academicYears = AcademicYear::where('school_id', $school_id)->get();
        $sections = Section::where("school_id", $school_id)->get();
        $teachers = User::where('usergroup_id', 5)->where("school_id", $school_id)->get();
        $terms = AcademicTerm::where("school_id", $school_id)->get();
        $examTypes =  ExamType::all();
        // dd($subjects);
        return view("admin.exams.create", compact(
            "exam", "subjects", "standards", "academicYears", "sections", "teachers", "terms", "examTypes"
            ));
    }
    public function update(UpdateExamsRequest $request, string $exam){
            // dd($request);

    $validated = $request->validated();
    Exam::where("id", $exam)->update($validated);
    return redirect()->route("admin.exams")->with("successmessage", "Exam updated successfully!");
    }

    public function archieve(string $exam){
        $school_id = Auth::user()->school_id;
        Exam::where("id", $exam)->where("school_id", $school_id)->delete();
        return redirect()->route("admin.exams")->with("successmessage", "Exam deleted successfully!");
    }
}