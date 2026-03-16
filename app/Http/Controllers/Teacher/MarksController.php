<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Subject;
use App\Models\User;
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
    $exam = Exam::findOrFail($exam_id);
    // Add security check: school_id === auth()->user()->school_id

    $standard = $exam->standard; // assuming relation
    $subject  = $request->subject_id ? Subject::find($request->subject_id) : null;

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
    $exams = Exam::where('school_id', $schoolId)
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

    // Add progress info
    foreach ($exams as $exam) {
        $exam->entered_count = Marks::where('exam_id', $exam->id)
            ->where('teacher_id', $teacher->id) // only count what this teacher entered
            ->count();
    }

    return view('teacher.marks.teacher-exam-list', compact('exams'));
}

public function enterExamMarks(Exam $exam)
{
    $user = Auth::user();
    if ($exam->school_id !== $user->school_id) {
        abort(403, 'Not your school');
    }

    // Optional: check if this teacher is allowed to enter for this exam
    // if (!$exam->isAssignedToTeacher($user)) abort(403);

    $standard = $exam->standard; // adjust relation name
    $subject = request('subject_id') ? Subject::find(request('subject_id')) : null;

    $students = User::where('school_id', $user->school_id)
        ->whereHas('userprofile', fn($q) => $q->where('standard_id', $standard->id))
        ->orderBy('name')
        ->get();

    $existing = Marks::where('exam_id', $exam->id)
        ->where('school_id', $exam->school_id)
        ->when($subject, fn($q) => $q->where('subject_id', $subject->id))
        ->get()
        ->keyBy('student_id')
        ->map(fn($m) => ['marks' => $m->marks, 'comment' => $m->comment]);

    return view('marks.enter', compact('exam', 'standard', 'subject', 'students', 'existing'));
}

public function saveExamMarks(Request $request, Exam $exam)
{
    $user = Auth::user();
    if ($exam->school_id !== $user->school_id) {
        abort(403);
    }

    $validated = $request->validate([
        'marks.*'    => 'nullable|numeric|min:0|max:100',
        'comments.*' => 'nullable|string|max:255',
    ]);

    $saved = 0;
    $teacherId = $user->id;

    foreach ($validated['marks'] ?? [] as $studentId => $mark) {
        if ($mark === null || trim($mark) === '') continue;

        Marks::updateOrCreate(
            [
                'student_id' => $studentId,
                'exam_id'    => $exam->id,
                'school_id'  => $exam->school_id,
                'subject_id' => $request->subject_id ?? null,
            ],
            [
                'teacher_id' => $teacherId,
                'marks'      => $mark,
                'comment'    => $request->comments[$studentId] ?? null,
            ]
        );
        $saved++;
    }

    return redirect()
        ->route('teacher.exam.marks')
        ->with('success', $saved . ' marks saved for ' . $exam->name);
}
}