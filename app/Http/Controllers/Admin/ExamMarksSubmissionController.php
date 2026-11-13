<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\Section;
use App\Models\User;
use App\Services\Marks\MarksSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ExamMarksSubmissionController extends Controller
{
    public function __construct(private MarksSubmissionService $submissions) {}

    public function index(Request $request)
    {
        $actor = $this->actor();
        $schoolId = (int) $actor->school_id;
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : 0;
        $examId = $request->filled('exam_id') ? (int) $request->input('exam_id') : 0;

        $waiting = $this->submissions->awaitingReview($schoolId);
        $filterIds = (clone $waiting)->get(['class_id', 'exam_id']);

        $submissions = (clone $waiting)
            ->with(['exam.examType', 'exam.academicTerm', 'exam.section', 'class', 'subject', 'teacher'])
            ->when($classId > 0, fn ($query) => $query->where('class_id', $classId))
            ->when($examId > 0, fn ($query) => $query->where('exam_id', $examId))
            ->paginate(20)
            ->withQueryString();

        $pendingCount = $this->submissions->pendingCount($schoolId);
        $classOptions = Section::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $filterIds->pluck('class_id')->map(fn ($id) => (int) $id)->unique()->all())
            ->orderBy('name')
            ->get(['id', 'name']);
        $examOptions = Exam::query()
            ->with(['examType', 'section', 'subject'])
            ->where('school_id', $schoolId)
            ->whereIn('id', $filterIds->pluck('exam_id')->map(fn ($id) => (int) $id)->unique()->all())
            ->orderBy('id')
            ->get();
        $markStats = $this->markStats($schoolId, $submissions->getCollection());
        $releaseLocks = $this->releaseLocks($schoolId, $submissions->getCollection());
        $canReopen = $this->canReopen($actor);

        return view('admin.marks.submissions', compact(
            'submissions',
            'pendingCount',
            'classOptions',
            'examOptions',
            'markStats',
            'releaseLocks',
            'canReopen',
        ));
    }

    public function show(Exam $exam)
    {
        $actor = $this->actor();
        $this->assertExamSchool($exam);

        $exam->load(['section', 'subject', 'standard']);

        $submissions = ExamMarksSubmission::query()
            ->where('school_id', $exam->school_id)
            ->where('exam_id', $exam->id)
            ->with(['class', 'subject', 'teacher', 'reopenedBy', 'approvedBy', 'rejectedBy'])
            ->get();

        $releaseLocks = $this->releaseLocks((int) $exam->school_id, $submissions);
        $canReopen = $this->canReopen($actor);

        return view('admin.marks.submission-detail', compact('exam', 'submissions', 'releaseLocks', 'canReopen'));
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

    /**
     * Reopen is offered to a site admin, school admin, or head teacher of this school.
     * The service still refuses anyone outside its existing review groups.
     */
    private function canReopen(User $actor): bool
    {
        return in_array((int) $actor->usergroup_id, [1, 3, 4], true) && $actor->school_id !== null;
    }

    /**
     * @param  Collection<int, ExamMarksSubmission>  $submissions
     * @return array<int, array{count:int, average:?string, min:?string, max:?string}>
     */
    private function markStats(int $schoolId, Collection $submissions): array
    {
        if ($submissions->isEmpty()) {
            return [];
        }

        $marks = Marks::query()
            ->where('school_id', $schoolId)
            ->whereIn('exam_id', $submissions->pluck('exam_id')->map(fn ($id) => (int) $id)->unique()->all())
            ->get(['exam_id', 'subject_id', 'section_id', 'marks', 'absent', 'grade']);

        $stats = [];
        foreach ($submissions as $submission) {
            $rows = $marks->filter(function (Marks $mark) use ($submission) {
                return (int) $mark->exam_id === (int) $submission->exam_id
                    && (int) $mark->subject_id === (int) $submission->subject_id
                    && (int) $mark->section_id === (int) $submission->class_id;
            });
            $numeric = $rows->filter(fn (Marks $mark) => ! $mark->isAbsent() && $mark->marks !== null)->pluck('marks');
            $stats[(int) $submission->id] = [
                'count' => $rows->count(),
                'average' => $numeric->isEmpty() ? null : $this->markNumber($numeric->avg()),
                'min' => $numeric->isEmpty() ? null : $this->markNumber($numeric->min()),
                'max' => $numeric->isEmpty() ? null : $this->markNumber($numeric->max()),
            ];
        }

        return $stats;
    }

    private function markNumber(mixed $value): string
    {
        $number = (float) $value;

        return fmod($number, 1.0) === 0.0 ? (string) (int) $number : number_format($number, 1);
    }

    /**
     * Latest released sitting for each submission, from marks_releases.
     * The releaser is loaded by id inside this school.
     *
     * @param  Collection<int, ExamMarksSubmission>  $submissions
     * @return array<int, array{at:mixed, timezone:string, name:string, release_id:int}>
     */
    private function releaseLocks(int $schoolId, Collection $submissions): array
    {
        if ($submissions->isEmpty()) {
            return [];
        }

        $examIds = $submissions->pluck('exam_id')->map(fn ($id) => (int) $id)->unique()->all();
        $anchors = Exam::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $examIds)
            ->get()
            ->keyBy('id');
        if ($anchors->isEmpty()) {
            return [];
        }

        $releases = MarksRelease::query()
            ->with(['standardLink', 'exam'])
            ->where('school_id', $schoolId)
            ->where('status', MarksRelease::STATUS_RELEASED)
            ->whereNotNull('released_at')
            ->whereHas('exam', function ($query) use ($schoolId, $anchors) {
                $query->where('school_id', $schoolId)->where(function ($sittings) use ($anchors) {
                    foreach ($anchors as $anchor) {
                        $sittings->orWhere(function ($one) use ($anchor) {
                            $one->where('exam_type_id', $anchor->exam_type_id)
                                ->where('academic_term_id', $anchor->academic_term_id);
                        });
                    }
                });
            })
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->get();

        $names = User::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $releases->pluck('created_by')->filter()->map(fn ($id) => (int) $id)->unique()->all())
            ->pluck('name', 'id');

        $locks = [];
        foreach ($submissions as $submission) {
            $anchor = $anchors->get((int) $submission->exam_id);
            if ($anchor === null) {
                continue;
            }
            $match = $releases->first(function (MarksRelease $release) use ($anchor, $submission) {
                $sameSitting = (int) $release->exam?->exam_type_id === (int) $anchor->exam_type_id
                    && (int) $release->exam?->academic_term_id === (int) $anchor->academic_term_id;
                if (! $sameSitting) {
                    return false;
                }
                if ($release->scope === 'school') {
                    return true;
                }

                return (int) ($release->standardLink?->section_id ?? 0) === (int) $submission->class_id;
            });
            if ($match === null) {
                continue;
            }
            $locks[(int) $submission->id] = [
                'at' => $match->released_at,
                'timezone' => $match->timezone ?: 'Africa/Kampala',
                'name' => (string) ($names[(int) $match->created_by] ?? 'Unknown'),
                'release_id' => (int) $match->id,
            ];
        }

        return $locks;
    }
}
