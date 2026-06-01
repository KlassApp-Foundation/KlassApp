<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;

use App\Models\Academics\Marks;
use App\Models\AcademicYear;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\GradingSystemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Events\MarksUpdated;
use App\Events\GradesPublished;

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
        abort(403, "You are not authorized.");
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
        $yrId = AcademicYear::where("school_id", $schoolId)->where("name", now()->year)->value("id");

    $exams = Exam::with(['standard', 'subject', 'teacher', 'academicYear'])
        -> where('school_id', $schoolId)
        ->where('academic_year_id', $yrId)
        ->where("teacher_id", $teacher->id)
        ->orderBy('created_at', 'desc')
        ->get();
   
    return view('teacher.marks.teacher-exam-list', compact('exams', "exm"));
}

// mark exam as done
public function TogglekStatus(Exam $exam){   
    $exam->changeExamStatus();
    return redirect()
        ->route('teacher.exam.marks')
        ->with('successmessage', ' Exam status updated!');
}

public function enterExamMarks(Exam $exam)
{
    $schoolId = Auth::user()->school_id;
        
     $allStudents = User::with(["school", "marks", "studentAcademic.standardLink"])
                    ->where("usergroup_id", 6)
                    ->where("school_id", $schoolId)
                    ->whereHas("studentAcademic", function($q) use($exam){
                        $q->whereHas("standardLink", function($q2) use($exam){
                            $q2->where("standard_id", $exam->standard_id)
                               ->where("section_id", $exam->section_id);
                        });
                    })
                  ->get();
     $total = $allStudents->count();             

    $exam = $exam->load("academicTerm", "section", "subject", "teacher");
    // dd($allStudents);

    return view('teacher.marks.enter', compact( "allStudents", "exam", "total"));
}

public function saveExamMarks(Request $request, Exam $exam, GradingSystemService $gradingSystem)
{
    $user = Auth::user();
    $schoolId = $user->school_id;
    if ($exam->school_id !== $schoolId) {
        abort(403, "Not Authorized");
    }
    foreach ($request->marks as $studentId => $mark) {
        if ($mark === null || trim($mark) === '') continue;
        $grade = $gradingSystem->grade($mark,$schoolId, $exam);
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
                "grade" => $grade,
                "section_id" => $exam->section_id
            ]
        );
    }

    // Notify school admins that marks were entered/updated
    try {
        event(new MarksUpdated($exam, $user));
    } catch (\Throwable $e) {
        \Log::warning("Failed to dispatch MarksUpdated: {$e->getMessage()}");
    }

    // Check if any student now has marks in ALL subjects → comprehensive notification
    try {
        $outbound = app(\App\Services\OutboundWhatsAppService::class);

        // Total subjects assigned to this standard/class
        $totalSubjects = Subject::where('school_id', $exam->school_id)
            ->where('standard_id', $exam->standard_id)
            ->where('is_active', 1)
            ->count();

        if ($totalSubjects > 0) {
            // All exams of the same exam type + term + class (this exam period)
            $periodExamIds = Exam::where('standard_id', $exam->standard_id)
                ->where('exam_type_id', $exam->exam_type_id)
                ->where('academic_term_id', $exam->academic_term_id)
                ->where('school_id', $exam->school_id)
                ->pluck('id');

            foreach (array_keys($request->marks) as $studentId) {
                $studentMarksCount = Marks::whereIn('exam_id', $periodExamIds)
                    ->where('student_id', $studentId)
                    ->count();

                // Student now has marks in all subjects → notify parent
                if ($studentMarksCount >= $totalSubjects) {
                    $sent = $outbound->notifyComprehensiveGrades($studentId, $exam->id);
                    \Log::info("GradesPublished: student {$studentId} completed all {$totalSubjects} subjects, sent {$sent} message(s)");
                }
            }
        }
    } catch (\Throwable $e) {
        \Log::warning("Failed to check/dispatch GradesPublished: {$e->getMessage()}");
    }

    // change exam status
    $exam->changeExamStatus();
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
    //   to add school id relationship and filter according to it
        $marks = Marks::with(["exam", "student", "subject", "teacher"])->where('teacher_id', $tr->id)

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
    // Optional: check if student actually belongs to this exam/standard
    // if ($student->user_id !== $exam->standard_id) {
    //     abort(404, "Student not in this class/exam.");
    // }

    return view('teacher.marks.edit-single', compact('exam', 'mark', "student", "marks"));
}

public function updateMark(Request $request, Exam $exam, User $student, GradingSystemService $gradingSystem)
{
    $teacher = Auth::user();
    $schoolId = $teacher->school_id;
    $validated = $request->validate([
        'marks'       => 'sometimes|nullable|numeric|min:0|max:100', // adjust rules to your system
        'grade'       => 'nullable|string|max:5',
    ]);
     $mark = $validated["marks"];
      $grade = $gradingSystem->grade($mark,$schoolId, $exam);
    // Find or create
    Marks::updateOrCreate(
        [
            'exam_id'     => $exam->id,
            'subject_id'  => $exam->subject_id,
            'student_id'  => $student->id,
            'teacher_id'  => $teacher->id,
            'school_id'   => $teacher->school_id,
        ],
        [
            'marks'       => $validated['marks'],
            'grade'       => $grade ?? null,
        ]
    );

    // Optional: flash message
    return redirect()
        ->route('teacher.exam.marks')
        ->with('successmessage', $student->name . "'s ". '  Marks updated!');
}

}
