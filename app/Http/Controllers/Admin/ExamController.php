<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateExamRequest;
use App\Http\Requests\UpdateExamsRequest;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;          // probably not needed if school_id from auth
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use App\Services\ExamMarksheetService;
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
    $academicYears = AcademicYear::where('school_id', $school_id)->where("end_date", ">", now()) ->get();
    // dd($academicYears);
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
            // dd($teachers);
    $examTypes =  ExamType::all();
    return view('admin.exams.create', compact(
        'exams', "teachers", "sections", "academicYears", "terms", "subjects", "examTypes"
        ));
}

    public function index()
    {
        $schoolId = Auth::user()->school_id;

        $exams = Exam::with([
            'standard',
            'section',
            'examType',
            'academicTerm',
            'subject',
            'teacher.userprofile',
        ])
            ->where('school_id', $schoolId)
            ->latest()
            ->get();

        $marksByExam = Marks::where('school_id', $schoolId)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->groupBy('exam_id');

        foreach ($exams as $exam) {
            $examMarks = $marksByExam->get($exam->id) ?? collect();
            $exam->has_marks = $examMarks->isNotEmpty();

            // Always display the exam record's own subject/teacher — marks rows and
            // class teacherlinks are the wrong source (blank until marks exist, or
            // wrong teacher when multiple teachers share a class).
            $exam->subjects_list = collect([$exam->subject?->name])->filter()->values();

            $teacherLabel = $exam->teacher?->display_name
                ?: $exam->teacher?->name
                ?: $exam->teacher?->email;
            $exam->teachers_list = collect([$teacherLabel])->filter()->values();
        }

        $standards = Standard::where('school_id', $schoolId)->get();
        $subjects = Subject::where('school_id', $schoolId)->get();
        $teachers = User::where('school_id', $schoolId)
            ->whereIn('usergroup_id', [3, 5])
            ->get();

        $headers = ["No", "Term", "Type", "Status", "Level", "Class", "Subject", "Teacher", "Actions"];

        return view('admin.exams.index', compact(
            'exams', 'standards', 'subjects', 'teachers', 'headers'
        ));
    }

    public function marksheet(Exam $exam, ExamMarksheetService $marksheets)
    {
        $schoolId = Auth::user()->school_id;
        $sheet = $marksheets->build($exam, (int) $schoolId);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MarksheetExport($sheet['headings'], $sheet['rows'], $sheet['title']),
            "{$sheet['title']}_marksheet.xlsx"
        );
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

    $school_id = Auth::user()->school_id;
    $validated = $request->validated();
    Exam::where("id", $exam)->where("school_id", $school_id)->update($validated);
    return redirect()->route("admin.exams")->with("successmessage", "Exam updated successfully!");
    }

    public function archive(string $exam){
        $school_id = Auth::user()->school_id;
        Exam::where("id", $exam)->where("school_id", $school_id)->delete();
        return redirect()->route("admin.exams")->with("successmessage", "Exam deleted successfully!");
    }
}
