<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Academics\Remarks;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

// $exm = Exam::where('teacher_id', $teacher->id)->get();
    // Add progress info
    // foreach ($exams as $exam) {
    //     $exam->entered_count = Marks::where('exam_id', $exam->id)
    //         ->where('teacher_id', $teacher->id)
    //         ->count();
    // }
    return view('teacher.marks.teacher-exam-list', compact('exams', "exm"));
}

public function enterExamMarks( $exam)
{
    $user = Auth::user();
    $schoolId = $user->school_id;

    $exams = Exam::findOrFail($exam);
        $tr = DB::table("class_teacher_links")->where("teacher_id", $user->id)->first();

    // $students = User::byStandard(1)->where("school_id", $user->school_id)
    // ->orderBy("name")->get();
    // $students = StandardLink::where("standard_id", $exam->standard_id)->get();
    $students = StudentAcademic::with(["user"])->where("standardLink_id", $tr->standardLink_id)
                   ->whereHas('user', function ($query) {
                   $query->where("usergroup_id", 6);
                   })
               ->get();
    // $students = User::byStandard($exam->standard_id)
    // ->where('school_id', $exam->school_id)
    // ->orderBy('name')
    // ->get();


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

    return view('teacher.marks.enter', compact('exams', "students", 'standard', 'subject', 'existing', "user", "remarks", "exam", "tr"));
}

public function saveExamMarks(Request $request, Exam $exam)
{
    $user = Auth::user();
    if ($exam->school_id !== $user->school_id) {
        abort(403, "Not Authorized");
    }
// dd($request->all());
    foreach ($request->marks as $studentId => $mark) {
        if ($mark === null || trim($mark) === '') continue;

        $grade = ($mark >= 80)
                ? "A"
                : (($mark >= 75)
                    ? "B"
                    : (($mark >= 65)
                        ? "C"
                        : "E"));
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
                'remark_id'    => $request->remark_id[$studentId] ?? null,
                "grade" => $grade
            ]
        );
    }

    return redirect()
        ->route('teacher.exam.marks')
        ->with('successmessage', ' marks saved!');
}

// view examMarks
// In your Teacher/ExamController.php (or wherever it lives)


public function viewExamMarks(Exam $exam)
{
    $tr=Auth::user();
    // 1. Get students in this exam's standard/class using your scope
    if ($exam->school_id !== $tr->school_id || $exam->teacher_id !== $tr->id) {
         abort(403, "You are not authorized to view this exam.");
     }
      // only needed columns
      $ll=StandardLink::where("standard_id", 2)->get();
// dd($exam);
    //   to add school id relationship and filter according to it
        $marks = Marks::with(["exam", "student", "subject", "teacher", "remark"])->where('teacher_id', $tr->id)

        ->where('exam_id', $exam->id)
        ->where("school_id", $tr->school_id)
        ->where("subject_id", $exam->subject_id)
        ->whereHas('student', function ($query) {
            $query->students();           // ← uses the scope!
        })->get();
    return view('teacher.marks.view', compact( "marks", "exms", "exam" ));    // ← recommended if you want all students

        // or 'marks', 'students' if you prefer separate

}

// edit user
public function editMark(Exam $exam, User $student, Marks $marks)
{
    $teacher = Auth::user();

    // Find the existing mark (or create a new one if allowed)
    $mark = Marks::firstOrNew([
        'exam_id'     => $exam->id,
        'subject_id'  => $exam->subject_id,
        'student_id'  => $student->student_id,
        'teacher_id'  => $exam->teacher_id,
        'school_id'   => $exam->school_id,
    ]);

    // Optional: security check
    if ($mark->exists && $mark->teacher_id !== $teacher->id) {
        abort(403, "You didn't enter this mark.");
    }
$remarks = Remarks::where("school_id", $teacher->school_id)->get();
    // Optional: check if student actually belongs to this exam/standard
    // if ($student->user_id !== $exam->standard_id) {
    //     abort(404, "Student not in this class/exam.");
    // }

    return view('teacher.marks.edit-single', compact('exam', 'mark', "student", "remarks", "marks"));
}

public function updateMark(Request $request, Exam $exam, User $student)
{
    $teacher = Auth::user();

    $validated = $request->validate([
        'marks'       => 'required|numeric|min:0|max:100', // adjust rules to your system
        'grade'       => 'nullable|string|max:5',
        'remark'      => 'nullable|string|max:255',
        // add other fields you have (attendance, etc.)
    ]);

    // Find or create
    $mark = Marks::updateOrCreate(
        [
            'exam_id'     => $exam->id,
            'subject_id'  => $exam->subject_id,
            'student_id'  => $student->id,
            'teacher_id'  => $teacher->id,
            'school_id'   => $teacher->school_id,
        ],
        [
            'marks'       => $validated['marks'],
            'grade'       => $validated['grade'] ?? null,
            'remark'      => $validated['remark'] ?? null,
            // add other fields + maybe 'updated_at' is auto
        ]
    );

    // Optional: flash message
    return redirect()
        ->route('teacher.exam.marks.view', [$exam]) // or wherever your list is
        ->with('successmessage', "Marks updated for {$student->name}!");
}

}
