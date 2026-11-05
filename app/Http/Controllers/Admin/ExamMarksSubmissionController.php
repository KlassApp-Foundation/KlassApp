<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\User;
use App\Services\Marks\MarksSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ExamMarksSubmissionController extends Controller
{
    public function __construct(private MarksSubmissionService $submissions) {}

    public function index(Request $request)
    {
        $schoolId = (int) Auth::user()->school_id;

        $submissions = $this->submissions->awaitingReview($schoolId)
            ->with(['exam.examType', 'exam.academicTerm', 'exam.section', 'class', 'subject', 'teacher'])
            ->paginate(20);

        $pendingCount = $this->submissions->pendingCount($schoolId);

        return view('admin.marks.submissions', compact('submissions', 'pendingCount'));
    }

    public function show(Exam $exam)
    {
        $this->assertExamSchool($exam);

        $exam->load(['section', 'subject', 'standard']);

        $submissions = ExamMarksSubmission::query()
            ->where('school_id', $exam->school_id)
            ->where('exam_id', $exam->id)
            ->with(['class', 'subject', 'teacher', 'reopenedBy', 'approvedBy', 'rejectedBy'])
            ->get();

        return view('admin.marks.submission-detail', compact('exam', 'submissions'));
    }

    public function lock(Request $request, Exam $exam)
    {
        $actor = $this->actor();
        $this->assertExamSchool($exam);

        $validated = $request->validate([
            'class_id' => 'required|integer',
            'subject_id' => 'required|integer',
        ]);

        $this->submissions->lock($actor, $exam, (int) $validated['class_id'], (int) $validated['subject_id']);

        return redirect()->back()->with('successmessage', 'Marks submission locked.');
    }

    public function reopen(Request $request, Exam $exam)
    {
        $actor = $this->actor();
        $this->assertExamSchool($exam);

        $validated = $request->validate([
            'class_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'reopen_reason' => 'required|string|max:500',
        ]);

        $this->submissions->reopen(
            $actor,
            $exam,
            (int) $validated['class_id'],
            (int) $validated['subject_id'],
            $validated['reopen_reason'],
        );

        return redirect()->back()->with('successmessage', 'Marks submission reopened. The teacher can edit marks again.');
    }

    public function approve(Request $request, Exam $exam)
    {
        $actor = $this->actor();
        $this->assertExamSchool($exam);

        $validated = $request->validate([
            'class_id' => 'nullable|integer',
            'subject_id' => 'nullable|integer',
        ]);

        $affected = $this->submissions->approve(
            $actor,
            $exam,
            isset($validated['class_id']) ? (int) $validated['class_id'] : null,
            isset($validated['subject_id']) ? (int) $validated['subject_id'] : null,
        );

        $message = $affected > 0
            ? $affected.' submission(s) approved.'
            : 'Those submissions were already approved.';

        return redirect()->back()->with('successmessage', $message);
    }

    public function reject(Request $request, Exam $exam)
    {
        $actor = $this->actor();
        $this->assertExamSchool($exam);

        $validated = $request->validate([
            'class_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'return_reason' => 'nullable|string|max:500',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $reason = trim((string) ($validated['return_reason'] ?? $validated['rejection_reason'] ?? ''));
        if ($reason === '') {
            throw ValidationException::withMessages([
                'return_reason' => 'Give a reason of at least 10 characters.',
            ]);
        }

        $this->submissions->returnToTeacher(
            $actor,
            $exam,
            (int) $validated['class_id'],
            (int) $validated['subject_id'],
            $reason,
        );

        return redirect()->back()->with('successmessage', 'Marks returned to the teacher.');
    }

    private function actor(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function assertExamSchool(Exam $exam): void
    {
        $user = $this->actor();
        if ((int) $exam->school_id !== (int) $user->school_id) {
            abort(403);
        }
    }
}
