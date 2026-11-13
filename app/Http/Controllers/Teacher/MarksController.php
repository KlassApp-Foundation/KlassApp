<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\GradingHelper;
use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\Academics\NurseryAssessment;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Academics\BaseClassExamRoster;
use App\Services\ExamAuthorization;
use App\Services\Marks\MarksSubmissionService;
use App\Services\MarksImport\MarksImportService;
use App\Services\ExamMarksheetService;
use App\Services\GradingSystemService;
use App\Services\Marks\ExamMarkWriter;
use App\Services\Results\CombinedSheetPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Events\MarksUpdated;
use App\Exceptions\MarksLockedException;
use App\Models\Academics\ExamMarksSubmission;
use App\Exports\CombinedMarksheetExport;
use App\Exports\Marks\MarksTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class MarksController extends Controller
{
    public function __construct(
        private ExamAuthorization $examAuthorization,
        private BaseClassExamRoster $roster,
    ) {
    }

    /**
     * Get existing marks for an exam (prefill for teacher/app)
     * GET /api/marks/exam/{exam}?subject_id=optional
     */
    public function enter(Request $request, $exam_id)
{
    $teacher = Auth::user();
    $exam = Exam::findOrFail($exam_id);
    $this->examAuthorization->authorizeOrAbort($teacher, $exam, 'You are not authorized.');


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

    $yrId = AcademicYear::where('school_id', $schoolId)->where('status', 1)->value('id')
        ?? AcademicYear::where('school_id', $schoolId)->where('name', (string) now()->year)->value('id');
    $ctSectionIds = $yrId
        ? $this->examAuthorization->sectionIdsForClassTeacher($teacher, (int) $schoolId, (int) $yrId)
        : [];

    $exams = Exam::with(['standard', 'subject', 'teacher', 'academicYear', 'section', 'examType', 'academicTerm'])
        ->where('school_id', $schoolId)
        ->when($yrId, fn ($q) => $q->where('academic_year_id', $yrId))
        ->where(function ($q) use ($teacher, $ctSectionIds) {
            $q->where('teacher_id', $teacher->id);
            if ($ctSectionIds !== []) {
                $q->orWhereIn('section_id', $ctSectionIds);
            }
        })
        ->orderBy('created_at', 'desc')
        ->get();

    $examsByClass = $exams->groupBy('section_id');

    $assignedStdLinks = StandardLink::where('school_id', $schoolId)
        ->where(function ($q) use ($teacher, $examsByClass) {
            $q->where('class_teacher_id', $teacher->id);
            if ($examsByClass->isNotEmpty()) {
                $q->orWhereIn('section_id', $examsByClass->keys());
            }
        })
        ->get()
        ->keyBy('section_id');

    $canCreateExams = $ctSectionIds !== [];

    $submissionLabels = ExamMarksSubmission::query()
        ->where('school_id', $schoolId)
        ->whereIn('exam_id', $exams->pluck('id'))
        ->get()
        ->keyBy(fn (ExamMarksSubmission $row) => $row->exam_id.'|'.$row->class_id.'|'.$row->subject_id);

    $entered = Marks::query()
        ->where('school_id', $schoolId)
        ->whereIn('exam_id', $exams->pluck('id'))
        ->whereNotNull('marks')
        ->selectRaw('exam_id, count(*) as aggregate')
        ->groupBy('exam_id')
        ->pluck('aggregate', 'exam_id');

    foreach ($exams as $exam) {
        $row = $submissionLabels->get($exam->id.'|'.$exam->section_id.'|'.$exam->subject_id);
        $exam->submission_label = $row ? $row->reviewLabel() : 'Not started';
        $exam->marks_entered = (int) ($entered[$exam->id] ?? 0);
        $exam->return_note = $this->returnedNoteForTeacher($row, $exam, $teacher);
    }

    return view('teacher.marks.teacher-exam-list', compact('exams', 'examsByClass', 'assignedStdLinks', 'canCreateExams'));
}

public function submitMarks(Exam $exam, MarksSubmissionService $submissions)
{
    /** @var User $teacher */
    $teacher = Auth::user();
    if (! $teacher instanceof User) {
        abort(403, 'Not Authorized');
    }
    $this->examAuthorization->authorizeOrAbort($teacher, $exam, 'You are not authorized to submit marks for this exam.');

    if (! $submissions->hasWorkToSubmit($exam)) {
        return redirect()
            ->route('teacher.exam.marks.enter', $exam)
            ->with('failmessage', 'Save marks before submitting them for review.');
    }

    $submissions->submit($exam, $teacher);

    return redirect()
        ->route('teacher.exam.marks.view', $exam)
        ->with('successmessage', 'Marks submitted for review.');
}

public function enterExamMarks(Exam $exam)
{
    /** @var User $teacher */
    $teacher = Auth::user();
    if (! $teacher instanceof User) {
        abort(403, 'Not Authorized');
    }
    $this->authorizeEntry($teacher, $exam);

    $schoolId = $teacher->school_id;
    $coversStreams = $this->roster->coversStreams($exam);
    $streamId = $coversStreams && request()->filled('stream') ? (int) request()->query('stream') : null;

     $allStudents = $this->roster->students($exam, $teacher, $streamId);
     $total = $allStudents->count();

     $savedMarks = Marks::query()
         ->where('exam_id', $exam->id)
         ->where('subject_id', $exam->subject_id)
         ->where('school_id', $schoolId)
         ->where(function ($query) {
             $query->whereNotNull('marks')->orWhere('absent', true);
         })
         ->get(['student_id', 'marks', 'absent', 'grade'])
         ->mapWithKeys(fn ($row) => [(int) $row->student_id => ($row->isAbsent() ? 'AB' : $this->markForInput($row->marks))])
         ->all();
     $savedMarkCount = count($savedMarks);

    $exam = $exam->load("academicTerm", "section", "subject", "teacher", "standard");

    // Detect nursery level for conditional rendering
    $isNursery = false;
    $domains = [];
    $ratings = [];
    $existingAssessments = collect();

    if ($exam->standard) {
        $levelType = GradingHelper::levelTypeForStandard($exam->standard);
        if ($levelType === 'nursery') {
            $isNursery = true;
            $domains = ['Literacy', 'Numeracy', 'Motor Skills', 'Social/Emotional'];
            $ratings = ['Excellent', 'Good', 'Satisfactory', 'Needs Improvement'];
            $existingAssessments = NurseryAssessment::where('exam_id', $exam->id)
                ->where('academic_term_id', $exam->academic_term_id)
                ->get()
                ->groupBy('student_id')
                ->map(fn($group) => $group->keyBy('domain'));
        }
    }

    return view('teacher.marks.enter', [
        'allStudents' => $allStudents,
        'exam' => $exam,
        'total' => $total,
        'isNursery' => $isNursery,
        'domains' => $domains,
        'ratings' => $ratings,
        'existingAssessments' => $existingAssessments,
        'correctionReasonRequired' => $this->correctionReasonRequired($exam),
        'savedMarkCount' => $savedMarkCount,
        'savedMarks' => $savedMarks,
        'canSubmit' => app(MarksSubmissionService::class)->hasWorkToSubmit($exam)
            && ! app(ExamMarkWriter::class)->writesAreRefused($exam),
        'returnNotice' => $this->returnNotice($exam, $teacher),
        'submissionState' => $this->submissionStateLabel($exam),
        'showStreamColumn' => $coversStreams,
        'entryStreams' => $coversStreams ? $this->roster->streamOptions($exam, $teacher) : [],
        'selectedStreamId' => $streamId,
    ]);
}

/**
 * A base-class exam opens to the class head and to each stream's subject
 * teacher. A stream-level exam keeps the subject-teacher and class-teacher check.
 */
private function authorizeEntry(User $actor, Exam $exam): void
{
    if ($this->roster->coversStreams($exam)) {
        $this->roster->assertCanEnter($actor, $exam);

        return;
    }

    $this->examAuthorization->authorizeOrAbort($actor, $exam, 'You are not authorized to enter marks for this exam.');
}

/**
 * Student ids in this save that actually carry a mark or a rating.
 *
 * @return list<int>
 */
private function submittedMarkStudentIds(Request $request): array
{
    $ids = [];
    foreach ($request->input('marks', []) as $studentId => $mark) {
        if ($mark === null || trim((string) $mark) === '') {
            continue;
        }
        $ids[] = (int) $studentId;
    }
    foreach ($request->input('assessments', []) as $studentId => $domainRatings) {
        if (! is_array($domainRatings)) {
            continue;
        }
        foreach ($domainRatings as $rating) {
            if ($rating !== null && trim((string) $rating) !== '') {
                $ids[] = (int) $studentId;
                break;
            }
        }
    }

    return array_values(array_unique($ids));
}

/**
 * Spreadsheet template for bulk marks import (M1): roster rows keyed by
 * registration_number with an empty mark cell per student. Same
 * authorization as the entry page; ?format=csv for a CSV variant.
 */
public function downloadTemplate(Request $request, Exam $exam, MarksImportService $service)
{
    /** @var User $teacher */
    $teacher = Auth::user();
    if (! $teacher instanceof User) {
        abort(403, 'Not Authorized');
    }
    $this->examAuthorization->authorizeOrAbort($teacher, $exam, 'You are not authorized to download this template.');

    $format = in_array($request->query('format'), ['csv', 'xlsx'], true) ? $request->query('format') : 'xlsx';
    $fileName = sprintf('marks-template-exam-%d.%s', $exam->id, $format);

    // CSV cannot carry a second sheet: the import preview warns that the exam could not be checked.
    if ($format === 'csv') {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MarksheetExport($service->templateHeadings(), $service->templateRows($exam, $teacher), 'Marks'),
            $fileName,
        );
    }

    return \Maatwebsite\Excel\Facades\Excel::download(
        new MarksTemplateExport($service->templateHeadings(), $service->templateRows($exam, $teacher), $service->templateInfoRows($exam)),
        $fileName,
    );
}

public function saveExamMarks(Request $request, Exam $exam, GradingSystemService $gradingSystem)
{
    /** @var User $user */
    $user = Auth::user();
    if (! $user instanceof User) {
        abort(403, 'Not Authorized');
    }

    $this->authorizeEntry($user, $exam);

    app(ExamMarkWriter::class)->assertWritable($exam);

    $this->assertSubmittedStudentsBelongToSchool($request, $exam);
    $this->roster->assertStudentsAllowed($user, $exam, $this->submittedMarkStudentIds($request));
    $this->checkSubmissionLocked($exam);

    // A save on an already-submitted exam is a correction → needs a reason.
    $reason = $this->requireCorrectionReason($request, $exam);
    $afterReopen = $this->wasReopened($exam);

    // ===== Nursery branch: save domain ratings instead of numeric marks =====
    $exam->load('standard');
    $isNursery = $exam->standard
        && GradingHelper::levelTypeForStandard($exam->standard) === 'nursery';

    if ($isNursery) {
        $validRatings = ['Excellent', 'Good', 'Satisfactory', 'Needs Improvement'];
        $affectedMarkCount = 0;

        foreach ($request->input('assessments', []) as $studentId => $domainRatings) {
            foreach ($domainRatings as $domain => $rating) {
                if ($rating === null || trim($rating) === '') {
                    continue;
                }
                if (!in_array($rating, $validRatings, true)) {
                    continue;
                }
                NurseryAssessment::updateOrCreate(
                    [
                        'student_id'       => $studentId,
                        'academic_term_id' => $exam->academic_term_id,
                        'exam_id'          => $exam->id,
                        'domain'           => $domain,
                    ],
                    [
                        'rating'  => $rating,
                        'remarks' => null,
                    ]
                );
                $affectedMarkCount++;
            }
        }

        $this->logMarksActivity($request, $exam, $user, 'marks.saved', $affectedMarkCount, $reason);

        return redirect()
            ->route('teacher.exam.marks.enter', $exam)
            ->with('successmessage', 'Nursery assessments saved!');
    }

    // ===== Standard branch: numeric marks =====
    $affectedMarkCount = 0;

    $writer = app(ExamMarkWriter::class);
    foreach ($request->input('marks', []) as $studentId => $mark) {
        if ($mark === null || trim((string) $mark) === '') continue;
        $writer->save($user, $exam, (int) $studentId, $mark, $reason);
        $affectedMarkCount++;
    }

    // School admins hear that marks changed. Parents hear about marks only from a release.
    try {
        event(new MarksUpdated($exam, $user, $reason, $afterReopen, $affectedMarkCount));
    } catch (\Throwable $e) {
        \Log::warning("Failed to dispatch MarksUpdated: {$e->getMessage()}");
    }

    $this->logMarksActivity($request, $exam, $user, 'marks.saved', $affectedMarkCount);

    return redirect()
        ->route('teacher.exam.marks.enter', $exam)
        ->with('successmessage', 'Marks saved!');
}

// view examMarks
// In your Teacher/ExamController.php (or wherever it lives)


public function viewExamMarks(Exam $exam)
{
    $tr = Auth::user();
    $this->examAuthorization->authorizeOrAbort($tr, $exam, 'You are not authorized to view this exam.');

    $exam->load(['examType', 'academicTerm', 'academicYear', 'subject', 'teacher']);

    // Show all marks for this exam/subject (CT may view marks stamped to subject teacher).
    $marks = Marks::with(['exam', 'student.userprofile', 'subject', 'teacher'])
        ->where('exam_id', $exam->id)
        ->where('school_id', $tr->school_id)
        ->where('subject_id', $exam->subject_id)
        ->whereHas('student', function ($query) {
            $query->students();
        })
        ->get();

    $submission = ExamMarksSubmission::query()
        ->where('school_id', $tr->school_id)
        ->where('exam_id', $exam->id)
        ->where('class_id', $exam->section_id)
        ->where('subject_id', $exam->subject_id)
        ->first();

    return view('teacher.marks.view', [
        'marks' => $marks,
        'exam' => $exam,
        'submission' => $submission,
        'returnNotice' => $tr instanceof User ? $this->returnNotice($exam, $tr) : null,
    ]);
}

public function downloadMarksheet(Exam $exam, ExamMarksheetService $marksheets)
{
    $teacher = Auth::user();
    $this->examAuthorization->authorizeOrAbort(
        $teacher,
        $exam,
        'You are not authorized to download this marksheet.'
    );

    $sheet = $marksheets->build($exam, (int) $teacher->school_id);

    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\MarksheetExport($sheet['headings'], $sheet['rows'], $sheet['title']),
        "{$sheet['title']}_marksheet.xlsx"
    );
}

public function combinedMarksheet(StandardLink $stdLink)
{
    $teacher = Auth::user();

    if ($stdLink->school_id !== $teacher->school_id) {
        abort(403);
    }

    $isClassTeacher = (int) $stdLink->class_teacher_id === (int) $teacher->id
        || (
            (int) optional($stdLink->section)->class_teacher_id === (int) $teacher->id
            && (int) optional($stdLink->section)->school_id === (int) $teacher->school_id
        );

    $assigned = $isClassTeacher
        || Exam::where('school_id', $teacher->school_id)
            ->where('section_id', $stdLink->section_id)
            ->where('teacher_id', $teacher->id)
            ->exists();

    if (! $assigned) {
        abort(403, 'You are not assigned to this class.');
    }

    $term = AcademicTerm::where('school_id', $teacher->school_id)
        ->where('status', 'current')
        ->first();

    if (!$term) {
        return back()->with('failmessage', 'No current term found.');
    }

    $className = str_replace(' ', '_', $stdLink->section?->name ?? 'class');

    return Excel::download(
        new CombinedMarksheetExport($stdLink, $term),
        "combined_marksheet_{$className}.xlsx"
    );
}

public function combinedSheet(Request $request, CombinedSheetPage $sheets)
{
    $teacher = Auth::user();

    return view('teacher.marks.sheet', $sheets->viewData(
        (int) $teacher->school_id,
        $request,
        $sheets->sectionIdsFor($teacher),
        'teacher.marks.sheet',
        'teacher.marks.sheet.download',
        false,
    ));
}

public function downloadCombinedSheet(Request $request, CombinedSheetPage $sheets)
{
    $teacher = Auth::user();

    return $sheets->download(
        (int) $teacher->school_id,
        $request,
        $sheets->sectionIdsFor($teacher),
    );
}

// edit user
public function editMark(Exam $exam, User $student, Marks $marks)
{
    $teacher = Auth::user();
    $this->examAuthorization->authorizeOrAbort($teacher, $exam);

    $this->checkSubmissionLocked($exam);

    // Find the existing mark (or create a new one if allowed)
    $mark = Marks::firstOrNew([
        'exam_id'     => $exam->id,
        'subject_id'  => $exam->subject_id,
        'student_id'  => $student->id,
        'school_id'   => $exam->school_id,
    ]);

    // Optional: check if student actually belongs to this exam/standard
    // if ($student->user_id !== $exam->standard_id) {
    //     abort(404, "Student not in this class/exam.");
    // }

    return view('teacher.marks.edit-single', [
        'exam' => $exam,
        'mark' => $mark,
        'student' => $student,
        'marks' => $marks,
        'correctionReasonRequired' => $this->correctionReasonRequired($exam),
    ]);
}

public function updateMark(Request $request, Exam $exam, User $student, GradingSystemService $gradingSystem)
{
    /** @var User $teacher */
    $teacher = Auth::user();
    if (! $teacher instanceof User) {
        abort(403, 'Not Authorized');
    }

    $this->authorizeEntry($teacher, $exam);

    app(ExamMarkWriter::class)->assertWritable($exam);

    $schoolId = $teacher->school_id;
    $this->roster->assertStudentsAllowed($teacher, $exam, [(int) $student->id]);

    if (
        $student->school_id !== $schoolId
        || (int) $student->usergroup_id !== 6
    ) {
        abort(403, 'Not Authorized');
    }

    $this->checkSubmissionLocked($exam);

    $validated = $request->validate([
        'marks'       => 'sometimes|nullable|numeric|min:0|max:100', // adjust rules to your system
        'grade'       => 'nullable|string|max:5',
    ]);

    // Correcting marks on an exam that was already submitted is a correction:
    // it needs a reason, exactly like an admin reopening a locked submission.
    $reason = $this->requireCorrectionReason($request, $exam);
    $afterReopen = $this->wasReopened($exam);
    app(ExamMarkWriter::class)->save(
        $teacher,
        $exam,
        (int) $student->id,
        $validated['marks'] ?? null,
        $reason,
    );

    $this->logMarksActivity($request, $exam, $teacher, 'marks.updated', 1, $reason);

    // Let the school admins know — especially when they reopened this submission.
    try {
        event(new MarksUpdated($exam, $teacher, $reason, $afterReopen, 1));
    } catch (\Throwable $e) {
        \Log::warning("Failed to dispatch MarksUpdated: {$e->getMessage()}");
    }

    return redirect()
        ->route('teacher.exam.marks')
        ->with('successmessage', ($student->displayName ?: $student->name) . "'s ". '  Marks updated!');
}

private function submissionStateLabel(Exam $exam): string
{
    $row = ExamMarksSubmission::query()
        ->where('school_id', $exam->school_id)
        ->where('exam_id', $exam->id)
        ->where('class_id', $exam->section_id)
        ->where('subject_id', $exam->subject_id)
        ->first();

    return $row ? $row->reviewLabel() : 'Not started';
}

/**
 * A return reason is shown to the exam's teacher and to school admins.
 */
private function returnNotice(Exam $exam, User $viewer): ?string
{
    $row = ExamMarksSubmission::query()
        ->where('school_id', $exam->school_id)
        ->where('exam_id', $exam->id)
        ->where('class_id', $exam->section_id)
        ->where('subject_id', $exam->subject_id)
        ->first();

    if ($row === null || ! $row->isReturned()) {
        return null;
    }

    $isExamTeacher = (int) $viewer->id === (int) $exam->teacher_id;
    $isSchoolAdmin = in_array((int) $viewer->usergroup_id, [1, 2, 3], true)
        && (int) $viewer->school_id === (int) $exam->school_id;

    if (! $isExamTeacher && ! $isSchoolAdmin) {
        return null;
    }

    $reason = trim((string) ($row->rejection_reason ?: $row->reopen_reason));

    return $reason !== '' ? $reason : null;
}

/**
 * The return note on "Your exams to mark", for the exam's own teacher only.
 */
private function returnedNoteForTeacher(?ExamMarksSubmission $row, Exam $exam, User $teacher): ?string
{
    if ($row === null || ! $row->isReturned() || (int) $exam->teacher_id !== (int) $teacher->id) {
        return null;
    }

    $reason = trim((string) $row->rejection_reason);
    if ($reason === '') {
        $reason = trim((string) $row->reopen_reason);
    }

    return $reason !== '' ? $reason : null;
}

private function markForInput(mixed $mark): string
{
    if ($mark === null || $mark === '') {
        return '';
    }

    $text = rtrim(rtrim(number_format((float) $mark, 2, '.', ''), '0'), '.');

    return $text === '' ? '0' : $text;
}

private function logMarksActivity(
    Request $request,
    Exam $exam,
    User $teacher,
    string $action,
    int $affectedMarkCount,
    ?string $correctionReason = null
): void {
    activity()
        ->performedOn($exam)
        ->causedBy($teacher)
        ->withProperties([
            'school_id' => (int) $exam->school_id,
            'exam_id' => (int) $exam->id,
            'section_id' => (int) $exam->section_id,
            'subject_id' => (int) $exam->subject_id,
            'teacher_id' => (int) $teacher->id,
            'affected_mark_count' => $affectedMarkCount,
            'is_correction' => $correctionReason !== null,
            'correction_reason' => $correctionReason,
            'request_id' => $request->header('X-Request-ID') ?? (string) Str::uuid(),
        ])
        ->useLog('marks')
        ->log($action);
}

/**
 * A reason is required once the exam has been submitted — further marks edits
 * are corrections, matching the admin reopen reason requirement.
 */
private function correctionReasonRequired(Exam $exam): bool
{
    return app(ExamMarkWriter::class)->requiresCorrection($exam);
}

/**
 * Validate + return the correction reason when one is required.
 */
private function requireCorrectionReason(Request $request, Exam $exam): ?string
{
    $reason = trim((string) $request->input('correction_reason', ''));

    if (! $this->correctionReasonRequired($exam)) {
        return $reason === '' ? null : $reason;
    }

    if (mb_strlen($reason) < 10) {
        throw ValidationException::withMessages([
            'correction_reason' => 'These marks were already submitted. Give a reason of at least 10 characters for correcting them.',
        ]);
    }

    if (mb_strlen($reason) > 500) {
        throw ValidationException::withMessages([
            'correction_reason' => 'Keep the correction reason under 500 characters.',
        ]);
    }

    return $reason;
}

/** Was this exam's marks submission reopened by an admin? */
private function wasReopened(Exam $exam): bool
{
    return ExamMarksSubmission::query()
        ->where('school_id', $exam->school_id)
        ->where('exam_id', $exam->id)
        ->where('class_id', $exam->section_id)
        ->where('subject_id', $exam->subject_id)
        ->where('status', 'reopened')
        ->exists();
}

private function assertSubmittedStudentsBelongToSchool(Request $request, Exam $exam): void
{
    $studentIds = array_unique(array_merge(
        array_keys($request->input('marks', [])),
        array_keys($request->input('assessments', [])),
    ));

    if ($studentIds === []) {
        return;
    }

    $validStudentCount = User::query()
        ->whereIn('id', $studentIds)
        ->where('school_id', $exam->school_id)
        ->where('usergroup_id', 6)
        ->count();

    if ($validStudentCount !== count($studentIds)) {
        abort(403, 'Submitted student is not authorized for this school.');
    }
}

    /**
     * Check whether the submission for this exam+class+subject is locked.
     *
     * @throws MarksLockedException
     */
    private function checkSubmissionLocked(Exam $exam): void
    {
        $submission = ExamMarksSubmission::query()
            ->where('school_id', $exam->school_id)
            ->where('exam_id', $exam->id)
            ->where('class_id', $exam->section_id)
            ->where('subject_id', $exam->subject_id)
            ->first();

        if ($submission && $submission->isLocked()) {
            $deadline = $submission->deadline
                ? $submission->deadline->format('j M Y H:i')
                : null;
            throw new MarksLockedException($deadline);
        }
    }
}
