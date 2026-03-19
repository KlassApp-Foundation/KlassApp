<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Academics\Remarks;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MarksController extends Controller
{
    /**
     * Get existing marks for an exam (prefill for teacher/app)
     * GET /api/marks/exam/{exam}?subject_id=optional
     */
    public function enter(Request $request, $exam_id)
{
    $teacher = Auth::user();
    $exam = Exam::findOrFail($exam_id);
    if($teacher->id !== $exam->teacher_id){
        abort();
    }
    // Add security check: school_id === auth()->user()->school_id
    $standard = $exam->standard_id; // assuming relation
    $subject  = $request->subject_id;
    $students = User::whereHas('userprofile', function($q) use ($standard) {
            $q->where('standard_id', $standard->id); // adjust relation name if different
        })
        ->where('school_id', auth()->user()->school_id)
        ->orderBy('name')
        ->get();

    // Prefill existing marks
    $existing = Marks::where('exam_id', $exam->id)
        ->where('school_id', $exam->school_id)
        ->when($subject, fn($q) => $q->where('subject_id', $subject->id))
        ->get()
        ->keyBy('student_id')
        ->map(fn($m) => ['marks' => $m->marks, 'comment' => $m->comment]);
    return view('marks.enter', compact('exam', 'standard', 'subject', 'students', 'existing'));
}

public function teacherExamMarksList()
{
    $teacher = Auth::user();
    $schoolId = $teacher->school_id;

    // Get exams where this teacher is assigned (adjust based on your logic)
    // Example: exams where teacher is linked via subject or directly
    $exams = Exam::with(['standard', 'subject', 'teacher', 'academicYear'])
        -> where('school_id', $schoolId)
        ->where('academic_year_id', SiteHelper::getAcademicYear($schoolId)->id)
        ->where(function ($q) use ($teacher) {
            // Option 1: teacher is assigned to the exam directly
            $q->where('teacher_id', $teacher->id)
              // Option 2: teacher teaches one of the subjects in the exam
              ->orWhereHas('subject', function ($sq) use ($teacher) {
                  $sq->where('teacher_id', $teacher->id); // adjust relation
              });
        })
        ->orderBy('created_at', 'desc')
        ->get();
$exm = Exam::where('teacher_id', $teacher->id)->get();
    // Add progress info
    foreach ($exams as $exam) {
        $exam->entered_count = Marks::where('exam_id', $exam->id)
            ->where('teacher_id', $teacher->id) // only count what this teacher entered
            ->count();
    }

    return view('teacher.marks.teacher-exam-list', compact('exams', "exm"));
}

public function enterExamMarks( $exam)
{
    $user = Auth::user();
    $schoolId = $user->school_id;

    $exams = Exam::findOrFail($exam);
    $students = User::byStandard(1)->where("school_id", $user->school_id)
    ->orderBy("name")->get();
    $remarks = Remarks::where("school_id", 5)->get();
    // if ($exam->school_id !== $user->school_id) {
    //     abort(403, 'Not your school');
    // }

    $standard = $exam->standard; // adjust relation name
    // $subject = request('subject_id') ? Subject::find($exam) : null;
    $subject = $exams->subject;


    $existing = Marks::where('exam_id', $exam->id)
        ->where('school_id', $exam->school_id)
        ->when($subject, fn($q) => $q->where('subject_id', $subject->id))
        ->get()
        ->keyBy('student_id')
        ->map(fn($m) => ['marks' => $m->marks, 'comment' => $m->comment]);

    return view('teacher.marks.enter', compact('exams', "students", 'standard', 'subject', 'existing', "user", "remarks", "exam"));
}

public function saveExamMarks(Request $request, Exam $exam)
{
    $user = Auth::user();
    if ($exam->school_id !== $user->school_id) {
        abort(403, "Not Authorized");
    }

    foreach ($request->marks as $studentId => $mark) {
        if ($mark === null || trim($mark) === '') continue;
        Marks::updateOrCreate(
            [
                'student_id' => $studentId,
                'exam_id'    => $exam->id,
                'school_id'  => $exam->school_id,
                'subject_id' => $exam->subject_id,
            ],
            [
                'teacher_id' => $exam->teacher_id,
                'marks'      => $mark,
                'remark_id'    => $mark->remark_id[$studentId] ?? null,
            ]
        );
    }

    return redirect()
        ->route('teacher.exam.marks')
        ->with('successmessage', ' marks saved for ' . $exam->name . "!");
}

// view examMarks
// In your Teacher/ExamController.php (or wherever it lives)


public function viewExamMarks(Exam $exam, Subject $subject)
{
    $tr=Auth::user();
    // 1. Get students in this exam's standard/class using your scope
    $students = User::byStandard($exam->standard_id)     // ← your scope call here
        ->where('usergroup_id', 6)                       // or whatever filter you use for students
        ->orderBy('name', 'asc')
        ->get();                          // only needed columns

        $marks = Marks::with(["exam", "student", "subject", "teacher"])->get();
    return view('teacher.marks.view', compact( "marks", "exam" ));    // ← recommended if you want all students
        // or 'marks', 'students' if you prefer separate
   
}
}