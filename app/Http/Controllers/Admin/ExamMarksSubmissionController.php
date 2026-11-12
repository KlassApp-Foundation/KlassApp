<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\User;
use App\Helpers\SiteHelper;
use App\Services\ExamAuthorization;
use App\Services\Marks\MarksSubmissionService;
use App\Support\SchoolAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ExamMarksSubmissionController extends Controller
{
    public function __construct(private MarksSubmissionService $submissions) {}

    public function index(Request $request)
    {
        $actor = $this->actor();
        $schoolId = (int) $actor->school_id;
        $sections = $this->reviewSections($actor, $schoolId);

        $submissions = $this->submissions->awaitingReview($schoolId, $sections)
            ->with(['exam.examType', 'exam.academicTerm', 'exam.section', 'class', 'subject', 'teacher'])
            ->paginate(20);

        $pendingCount = $this->submissions->pendingCount($schoolId, $sections);

        $view = (int) $actor->usergroup_id === 5 ? 'teacher.marks.approvals' : 'admin.marks.submissions';

        return view($view, compact('submissions', 'pendingCount'));
    }

    /**
     * null for a full admin (all classes), a list of section ids for a class
     * teacher, 403 for anyone else.
     */
    private function reviewSections(User $actor, int $schoolId): ?array
    {
        if ((int) $actor->school_id > 0 && in_array((int) $actor->usergroup_id, SchoolAdmin::GROUPS, true)) {
            return null;
        }

        if ((int) $actor->usergroup_id !== 5) {
            abort(403);
        }

        $year = SiteHelper::getAcademicYear($schoolId);

        return app(ExamAuthorization::class)->sectionIdsForClassTeacher($actor, $schoolId, (int) ($year?->id ?? 0));
    }

    public function show(Exam $exam)
    {
        $actor = $this->actor();
        $this->assertExamSchool($exam);

        if ((int) $actor->usergroup_id === 5
            && ! app(ExamAuthorization::class)->isClassTeacherForExam($actor, $exam)) {
            abort(403, 'You can only review marks for the class you head.');
        }

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

    public function approveSelected(Request $request)
    {
        $actor = $this->actor();
        $validated = $request->validate([
            'submission_ids' => ['required', 'array', 'min:1'],
            'submission_ids.*' => ['integer'],
        ]);

        $result = $this->submissions->approveSelected($actor, $validated['submission_ids']);

        return redirect()->back()->with(
            'successmessage',
            $result['approved'].' approved, '.$result['skipped'].' skipped.',
        );
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

        $message = match (true) {
            $affected === 0 => 'Those marks were already approved.',
            $affected === 1 => 'Marks approved.',
            default => $affected.' sets of marks approved.',
        };

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
