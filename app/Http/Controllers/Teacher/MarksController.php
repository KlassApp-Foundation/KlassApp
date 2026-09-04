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
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\ExamAuthorization;
use App\Services\GradingSystemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Events\MarksUpdated;
use App\Events\GradesPublished;
use App\Exceptions\MarksLockedException;
use App\Models\Academics\ExamMarksSubmission;
use App\Exports\CombinedMarksheetExport;
use Maatwebsite\Excel\Facades\Excel;

class MarksController extends Controller
{
    public function __construct(private ExamAuthorization $examAuthorization)
    {
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

    return view('teacher.marks.teacher-exam-list', compact('exams', 'examsByClass', 'assignedStdLinks', 'canCreateExams'));
}

// mark exam as done
public function TogglekStatus(Exam $exam){
    /** @var User $teacher */
    $teacher = Auth::user();
    if (! $teacher instanceof User) {
        abort(403, 'Not Authorized');
    }

    $this->examAuthorization->authorizeOrAbort($teacher, $exam);

    $exam->changeExamStatus();
    return redirect()
        ->route('teacher.exam.marks')
        ->with('successmessage', ' Exam status updated!');
}

public function enterExamMarks(Exam $exam)
{
    /** @var User $teacher */
    $teacher = Auth::user();
    if (! $teacher instanceof User) {
        abort(403, 'Not Authorized');
    }
    $this->examAuthorization->authorizeOrAbort($teacher, $exam, 'You are not authorized to enter marks for this exam.');

    $schoolId = $teacher->school_id;

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

    return view('teacher.marks.enter', compact(
        "allStudents", "exam", "total",
        "isNursery", "domains", "ratings", "existingAssessments"
    ));
}

public function saveExamMarks(Request $request, Exam $exam, GradingSystemService $gradingSystem)
{
    /** @var User $user */
    $user = Auth::user();
    if (! $user instanceof User) {
        abort(403, 'Not Authorized');
    }

    $this->examAuthorization->authorizeOrAbort($user, $exam);

    $this->assertSubmittedStudentsBelongToSchool($request, $exam);
    $this->checkSubmissionLocked($exam);

    $schoolId = $user->school_id;

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

        $exam->changeExamStatus();
        $this->logMarksActivity($request, $exam, $user, 'marks.saved', $affectedMarkCount);

        return redirect()
            ->route('teacher.exam.marks')
            ->with('successmessage', 'Nursery assessments saved!');
    }

    // ===== Standard branch: numeric marks =====
    $affectedMarkCount = 0;

    foreach ($request->input('marks', []) as $studentId => $mark) {
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
        $affectedMarkCount++;
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
            ->distinct('name')
            ->count('name');

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
    $this->logMarksActivity($request, $exam, $user, 'marks.saved', $affectedMarkCount);

    return redirect()
        ->route('teacher.exam.marks')
        ->with('successmessage', ' marks saved!');
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

    return view('teacher.marks.view', compact('marks', 'exam'));
}

public function downloadMarksheet(Exam $exam)
{
    $teacher = Auth::user();

    if ($exam->school_id !== $teacher->school_id) {
        abort(403, 'You are not authorized to download this marksheet.');
    }

    $schoolId = $teacher->school_id;

    $subjects = Marks::where('exam_id', $exam->id)
        ->join('subjects', 'marks.subject_id', '=', 'subjects.id')
        ->select('subjects.id', 'subjects.name')
        ->distinct()
        ->orderBy('subjects.name')
        ->get();

    $students = User::whereIn('id', function ($q) use ($exam) {
            $q->select('student_id')->from('marks')->where('exam_id', $exam->id)->distinct();
        })
        ->where('school_id', $schoolId)
        ->where('usergroup_id', 6)
        ->where('status', 'active')
        ->orderBy('name')
        ->get();

    $headings = array_merge(['STUDENT NAME'], $subjects->pluck('name')->toArray());
    $rows = [];

    foreach ($students as $student) {
        $row = [$student->name];
        foreach ($subjects as $subject) {
            $mark = Marks::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->where('subject_id', $subject->id)
                ->value('marks');
            $row[] = $mark !== null ? (float) $mark : '';
        }
        $rows[] = $row;
    }

    $title = str_replace(' ', '_', $exam->section?->name ?? 'class') . '_' . ($exam->examType?->code ?? 'exam');

    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\MarksheetExport($headings, $rows, $title),
        "{$title}_marksheet.xlsx"
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

    return view('teacher.marks.edit-single', compact('exam', 'mark', "student", "marks"));
}

public function updateMark(Request $request, Exam $exam, User $student, GradingSystemService $gradingSystem)
{
    /** @var User $teacher */
    $teacher = Auth::user();
    if (! $teacher instanceof User) {
        abort(403, 'Not Authorized');
    }

    $this->examAuthorization->authorizeOrAbort($teacher, $exam);

    $schoolId = $teacher->school_id;

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
     $mark = $validated["marks"];
      $grade = $gradingSystem->grade($mark,$schoolId, $exam);
    // Match saveExamMarks: key without actor teacher_id so CT updates the same row.
    Marks::updateOrCreate(
        [
            'exam_id'     => $exam->id,
            'subject_id'  => $exam->subject_id,
            'student_id'  => $student->id,
            'school_id'   => $exam->school_id,
        ],
        [
            'teacher_id'  => $exam->teacher_id,
            'marks'       => $validated['marks'],
            'grade'       => $grade ?? null,
            'section_id'  => $exam->section_id,
        ]
    );

    $this->logMarksActivity($request, $exam, $teacher, 'marks.updated', 1);

    // Optional: flash message
    return redirect()
        ->route('teacher.exam.marks')
        ->with('successmessage', ($student->displayName ?: $student->name) . "'s ". '  Marks updated!');
}

private function logMarksActivity(
    Request $request,
    Exam $exam,
    User $teacher,
    string $action,
    int $affectedMarkCount
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
            'request_id' => $request->header('X-Request-ID') ?? (string) Str::uuid(),
        ])
        ->useLog('marks')
        ->log($action);
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
        $submission = ExamMarksSubmission::where('exam_id', $exam->id)
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
