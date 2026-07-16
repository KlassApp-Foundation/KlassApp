<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ExamMarksSubmissionController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = Auth::user()->school_id;

        $exams = Exam::with(['subject', 'standard', 'section', 'academicTerm', 'examType'])
            ->forSchool($schoolId)
            ->where('status', 'submitted')
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        $stats = [];
        foreach ($exams as $exam) {
            $submissions = ExamMarksSubmission::forSchool($schoolId)
                ->where('exam_id', $exam->id);

            $stats[$exam->id] = [
                'total'    => (clone $submissions)->count(),
                'locked'   => (clone $submissions)->where('status', 'locked')->count(),
                'reopened' => (clone $submissions)->where('status', 'reopened')->count(),
                'approved' => (clone $submissions)->where('approval_status', 'approved')->count(),
                'pending'  => (clone $submissions)
                    ->where(function ($q) {
                        $q->where('approval_status', 'pending')
                          ->orWhereNull('approval_status');
                    })
                    ->count(),
            ];
        }

        return view('admin.marks.submissions', compact('exams', 'stats'));
    }

    public function show(Exam $exam)
    {
        $schoolId = Auth::user()->school_id;
        if ($exam->school_id !== $schoolId) {
            abort(403);
        }

        $submissions = ExamMarksSubmission::forSchool($schoolId)
            ->with(['class', 'subject', 'teacher', 'reopenedBy', 'approvedBy', 'rejectedBy'])
            ->where('exam_id', $exam->id)
            ->get();

        return view('admin.marks.submission-detail', compact('exam', 'submissions'));
    }

    public function lock(Request $request, Exam $exam)
    {
        $schoolId = Auth::user()->school_id;
        if ($exam->school_id !== $schoolId) {
            abort(403);
        }

        $validated = $request->validate([
            'class_id'   => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $submission = ExamMarksSubmission::where('exam_id', $exam->id)
            ->where('class_id', $validated['class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->first();

        if (!$submission) {
            throw ValidationException::withMessages([
                'submission' => 'No submission record found for this exam/class/subject combination.',
            ]);
        }

        if ($submission->isLocked()) {
            return redirect()->back()->withErrors([
                'submission' => 'This submission is already locked.',
            ]);
        }

        $submission->status = 'locked';
        $submission->locked_at = now();
        // Reset approval to pending when cycling back through locked (e.g. after reopen/edit)
        $submission->approval_status = 'pending';
        $submission->save();

        return redirect()->back()->with('successmessage', 'Marks submission locked successfully.');
    }

    /**
     * Reopen — uses the same transition as reject() but without the rejection metadata.
     */
    public function reopen(Request $request, Exam $exam)
    {
        $schoolId = Auth::user()->school_id;
        if ($exam->school_id !== $schoolId) {
            abort(403);
        }

        $validated = $request->validate([
            'class_id'      => 'required|exists:sections,id',
            'subject_id'    => 'required|exists:subjects,id',
            'reopen_reason' => 'required|string|min:10|max:500',
        ]);

        $submission = $this->findSubmission($exam, $validated['class_id'], $validated['subject_id']);

        if (!$submission->isLocked()) {
            return redirect()->back()->withErrors([
                'submission' => 'Only locked submissions can be reopened.',
            ]);
        }

        $this->transitionToReopened($submission, $validated['reopen_reason']);

        return redirect()->back()->with('successmessage', 'Marks submission reopened. Teachers can edit marks again.');
    }

    // ───── Approval ─────

    /**
     * Unified approval action supporting all three granularities:
     *
     *   Per class/subject:   exam_id + class_id + subject_id   (single row)
     *   Per subject, all classes: exam_id + subject_id          (all classes for that subject)
     *   All at once:         exam_id only                       (every row under the exam)
     *
     * The approval is blocked (with a clear error) on any submission
     * not in locked status. For the "all at once" scope specifically,
     * the entire action is rejected if ANY row isn't locked yet,
     * with a message naming what's outstanding.
     */
    public function approve(Request $request, Exam $exam)
    {
        $schoolId = Auth::user()->school_id;
        if ($exam->school_id !== $schoolId) {
            abort(403);
        }

        $validated = $request->validate([
            'class_id'   => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
        ]);

        $query = ExamMarksSubmission::forSchool($schoolId)->where('exam_id', $exam->id);

        // Determine the filter shape
        $hasClass = !empty($validated['class_id']);
        $hasSubject = !empty($validated['subject_id']);

        if ($hasClass && $hasSubject) {
            // Per class/subject — single row
            $query->where('class_id', $validated['class_id'])
                  ->where('subject_id', $validated['subject_id']);
        } elseif ($hasSubject) {
            // Per subject, all classes
            $query->where('subject_id', $validated['subject_id']);
        }
        // else: all at once (no additional filter)

        $targets = $query->get();

        if ($targets->isEmpty()) {
            return redirect()->back()->withErrors([
                'approval' => 'No matching submissions found to approve.',
            ]);
        }

        // Check every target is locked
        $notLocked = $targets->reject(fn ($s) => $s->isLocked());

        if ($notLocked->isNotEmpty()) {
            $details = $notLocked->map(fn ($s) => sprintf(
                'Class #%s / Subject #%s (status: %s)',
                $s->class_id,
                $s->subject_id,
                $s->status
            ))->implode('; ');

            return redirect()->back()->withErrors([
                'approval' => 'Only locked submissions can be approved. The following are not yet locked: ' . $details,
            ]);
        }

        // Check no target is already approved (idempotent — skip, don't error)
        $alreadyApproved = $targets->filter(fn ($s) => $s->isApproved());

        $now = now();
        $adminId = Auth::id();
        $affected = 0;

        foreach ($targets as $submission) {
            if ($submission->isApproved()) {
                continue; // idempotent — skip already-approved rows
            }
            $submission->approval_status = 'approved';
            $submission->approved_at = $now;
            $submission->approved_by = $adminId;
            $submission->save();
            $affected++;
        }

        $message = $affected
            ? "{$affected} submission(s) approved successfully."
            : 'All selected submissions were already approved.';

        if ($alreadyApproved->isNotEmpty()) {
            $message .= sprintf(' (%d were already approved, skipped.)', $alreadyApproved->count());
        }

        return redirect()->back()->with('successmessage', $message);
    }

    /**
     * Reject a single class/subject row.
     *
     * Rejection is always scoped to exactly one row (class_id + subject_id).
     * It reuses Part A's reopen mechanism (transitionToReopened) so the
     * underlying status goes back to "reopened" — the teacher can edit
     * again, and approval_status resets to "pending" when it cycles back
     * through "locked" again.
     */
    public function reject(Request $request, Exam $exam)
    {
        $schoolId = Auth::user()->school_id;
        if ($exam->school_id !== $schoolId) {
            abort(403);
        }

        $validated = $request->validate([
            'class_id'         => 'required|exists:sections,id',
            'subject_id'       => 'required|exists:subjects,id',
            'rejection_reason' => 'required|string|min:10|max:500',
        ]);

        $submission = $this->findSubmission($exam, $validated['class_id'], $validated['subject_id']);

        if (!$submission) {
            throw ValidationException::withMessages([
                'submission' => 'No submission record found for this exam/class/subject combination.',
            ]);
        }

        if (!$submission->isLocked()) {
            return redirect()->back()->withErrors([
                'submission' => 'Only locked submissions can be rejected.',
            ]);
        }

        // Reuse Part A's reopen mechanism — transitions status back to "reopened"
        $this->transitionToReopened($submission, 'Rejected: ' . $validated['rejection_reason']);

        // Set rejection metadata
        $submission->approval_status = 'rejected';
        $submission->rejected_at = now();
        $submission->rejected_by = Auth::id();
        $submission->rejection_reason = $validated['rejection_reason'];
        $submission->save();

        return redirect()->back()->with('successmessage', 'Submission rejected and reopened. The teacher can correct and resubmit.');
    }

    // ───── Internal helpers ─────

    /**
     * Find a submission by exam + class + subject.
     */
    private function findSubmission(Exam $exam, int $classId, int $subjectId): ?ExamMarksSubmission
    {
        return ExamMarksSubmission::where('exam_id', $exam->id)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->first();
    }

    /**
     * Reuse Part A's reopen logic: transition status to "reopened" with
     * timestamp, admin ID, and reason. Also resets approval_status to
     * pending (so the next lock cycle re-enters the approval queue).
     */
    private function transitionToReopened(ExamMarksSubmission $submission, string $reason): void
    {
        $submission->status = 'reopened';
        $submission->reopened_at = now();
        $submission->reopened_by = Auth::id();
        $submission->reopen_reason = $reason;
        $submission->approval_status = 'pending';
        $submission->save();
    }
}
