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
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
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

        $exams = Exam::with(['standard', 'section', 'examType', 'academicTerm', 'subject', 'teacher'])
            ->where('school_id', $schoolId)
            ->latest()
            ->get();

        $subjectNames = Subject::where('school_id', $schoolId)->pluck('name', 'id');

        $marksByExam = Marks::where('school_id', $schoolId)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->groupBy('exam_id');

        $stdLinks = StandardLink::where('school_id', $schoolId)->get();

        $teacherLinks = Teacherlink::where('school_id', $schoolId)
            ->get()
            ->groupBy('standardLink_id');

        foreach ($exams as $exam) {
            $examMarks = $marksByExam->get($exam->id) ?? collect();

            // Prefer marks-derived names when present; otherwise show the exam's own subject
            // (list used to show "-" for brand-new exams even when subject_id was saved).
            $exam->subjects_list = $examMarks->pluck('subject_id')->unique()
                ->map(fn ($id) => $subjectNames[$id] ?? null)
                ->filter()
                ->values();
            if ($exam->subjects_list->isEmpty() && $exam->subject?->name) {
                $exam->subjects_list = collect([$exam->subject->name]);
            }

            $exam->has_marks = $examMarks->isNotEmpty();

            $sl = $stdLinks->first(fn ($s) => $s->section_id == $exam->section_id && $s->standard_id == $exam->standard_id);

            $exam->teachers_list = $sl
                ? collect($teacherLinks->get($sl->id) ?? [])
                    ->map(fn ($tl) => $tl->teacher?->name ?: $tl->teacher?->email)
                    ->filter()
                    ->unique()
                    ->values()
                : collect();
            if ($exam->teachers_list->isEmpty() && ($exam->teacher?->name || $exam->teacher?->email)) {
                $exam->teachers_list = collect([
                    $exam->teacher->name ?: $exam->teacher->email,
                ]);
            }
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
