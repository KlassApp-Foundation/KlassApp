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
use App\Services\GradingSystemService;
use App\Services\StudentPromotionService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class MarksController extends Controller
{
    protected $studentPromotion;
    public function __construct(StudentPromotionService $studentPromotion)
    {
        $this->studentPromotion = $studentPromotion;
    }


// all marks according to exam and standard
 public function schoolMarksOverview()
{
    $schoolId = Auth::user()->school_id;

   $students = User::with(
    ["marks.subject", "marks.exam", "marks.student", "marks.teacher", "marks.school" ])
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
    public function classExamOverview(Request $request, GradingSystemService $gradingSystem)
{
    $schoolId = Auth::user()->school_id;
    $academic_year_id = AcademicYear::where("school_id", $schoolId)->where("description", "Current Academic Year")->value("id");
    
    // ============ eagerload users ===========
  $query = User::query()
    ->with(['marks' => function ($q) use ($request) {
        $q->with('exam', 'subject')
         ->whereHas("exam", function($e) use($request){
            $e->where("status", "submitted");
        });
              // by term
        $q->when($request->filled('term'), function ($q2) use ($request) {
            $q2->whereHas('exam', fn($e) => $e
            ->where('academic_term_id', $request->term));
        });

        $q->when($request->filled("class"), function($q2) use($request){
            // $q2->whereHas("")
            $q2->whereHas("exam", fn($e) =>$e ->where("section_id", $request->class));
            
        });
        // by exam type
        $q->when($request->filled("examType"), function($q2) use($request){
            // $q2->whereHas("")
            $q2->whereHas("exam", fn($e) =>$e ->where("exam_type_id", $request->examType));
            
        });

        $q->when($request->filled('year'), function ($q2) use ($request) {
            $q2->whereHas('exam', fn($e) => $e->where('academic_year_id', $request->year));
        });

         $q->when($request->filled('subject'), function ($q2) use ($request) {
            $q2->where('subject_id', $request->subject);
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
    // $marks = $query->paginate(15)->appends($request->query());

    // $students = $query->paginate(15)->appends($request->query());
    // get total exams done (subjects covered)
    $exams = Exam::where("school_id", $schoolId)
          ->where("section_id", $request->class)
          ->where("academic_term_id", $request->term)
          ->where("exam_type_id", $request->examType)
          ->where("academic_year_id", $academic_year_id);
    $examsDone = $exams->count();
    $exam = $exams->first();
        $students = $query->get();
        
        // dd($examsDone);
        // dd($students);
           // calculate total
           $students = $students->map(function ($student) use($examsDone, $exam, $gradingSystem) {
            $total = $student->marks->sum('marks');
             $aggregates = $gradingSystem->aggregates($student, $exam);
             $student->total = $total;
             $student->average = $examsDone ? $total / $examsDone : 0;
             $student->avg = $aggregates;
            // dd($aggregates);
           return $student;
       });
       $students = $students->sortByDesc("total")->values();
    //    dd($students->first()->marks);
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
       $examTypes = ExamType::all();
       $type = ExamType::find($request->examType);
       
    
    if($type?->name === "End Of Year"){
        $promotion = $this->studentPromotion->promoteStudents($students, $schoolId, $request->class, $examsDone);
        
    }
    //    paginate
    $page = request()->get("page", 1);
    $perpage = 6;
    $students = new LengthAwarePaginator(
        $students->forPage($page, $perpage),
        $students->count(),
        $perpage,
        $page,
        ["path" => request()->url(), "query" => request()->query()]
    );
    // $students->appends($request->query());
    // $students = $students->paginate(5)->appends($request->query());
    //    dd($position);
    // other filter options
    $years = AcademicYear::where('school_id', $schoolId)->get();
    $standards = Standard::where('school_id', $schoolId)->get();
    $classes = Section::where("school_id", $schoolId)->orderByDesc("id")->get();
    $subjects = Subject::where("school_id", $schoolId)->where("section_id", $request->class)->get();
   
    $subjectsCovered = Exam::where("school_id", $schoolId)
                       ->where("section_id", $request->class)
                       ->where("academic_term_id", $request->term)
                       ->distinct("subject_id")
                       ->count("subject_id");

    
    $terms = AcademicTerm::where("school_id", $schoolId)->get();
    $class = Section::find($request->class);
    $exam = Exam::where("school_id", $schoolId)
           ->where("section_id", $class->id)
           ->where("academic_year_id", $academic_year_id)
           ->where("academic_term_id", $request->term)
           ->whereHas('examType', fn($q) => $q->where('contributes_to_report_total', 1))
           ->first();
       $headers = ['Total', 'Average', 'Grade', 'Position', 'Actions'];    
       
    return view('admin.marks.filter', compact(
        'marks', 'year', "term", "class", "subjects", "years", "standards", "terms", "students", "classes", "examTypes",
         "type", "term", "subjectsCovered", "headers", "exam", "promotion"
        ));
}

// private function to compute grade
public function promoteStudents(Request $request)
{
    $schoolId = Auth::user()->school_id;
    $sectionId = $request->sectionId;
    $this->studentPromotion->finalizePromotions($schoolId, $sectionId);
    return redirect()->route("admin.marks.filter")->with("successmessage",  "Success, Learners have been promoted to the next class!");
   
}

// demote student
public function demoteStudent(){
    
}
}
