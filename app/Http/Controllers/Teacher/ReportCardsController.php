<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\StandardLink;
use App\Models\User;
use App\Services\StudentReportCardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReportCardsController extends Controller
{
    public function __construct(private StudentReportCardService $reports)
    {
    }

    public function index(): View
    {
        $teacher = $this->actor();
        $schoolId = (int) $teacher->school_id;

        $stdLinks = SiteHelper::getClassTeacherStandardLinks($schoolId, (int) $teacher->id)
            ->load(['section', 'standard']);

        $rows = $stdLinks->map(function (StandardLink $sl) use ($schoolId) {
            $exam = $this->reports->resolveExam($schoolId, $sl);
            $fill = $this->reports->subjectFillCounts($schoolId, $sl);
            $studentCount = 0;
            if ($exam) {
                $studentCount = $this->reports->studentIds($exam)->count();
            }

            return [
                'stdLink' => $sl,
                'hasExam' => $exam !== null,
                'studentCount' => $studentCount,
                'subjectsFilled' => $fill['filled'] ?? 0,
                'subjectsTotal' => $fill['total'] ?? 0,
            ];
        });

        return view('teacher.reports.cards.index', [
            'rows' => $rows,
        ]);
    }

    public function show(StandardLink $stdLink): View
    {
        $teacher = $this->actor();
        $this->authorizeClassTeacher($teacher, $stdLink);

        $schoolId = (int) $teacher->school_id;
        $exam = $this->reports->resolveExam($schoolId, $stdLink);
        $fill = $this->reports->subjectFillCounts($schoolId, $stdLink);

        $students = collect();
        if ($exam) {
            $ids = $this->reports->studentIds($exam);
            $students = User::whereIn('id', $ids)
                ->where('usergroup_id', 6)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $stdLink->load(['section', 'standard']);

        return view('teacher.reports.cards.show', [
            'stdLink' => $stdLink,
            'students' => $students,
            'hasExam' => $exam !== null,
            'subjectsFilled' => $fill['filled'] ?? 0,
            'subjectsTotal' => $fill['total'] ?? 0,
        ]);
    }

    public function previewStudent(StandardLink $stdLink, User $learner): SymfonyResponse
    {
        return $this->singleStudentResponse($stdLink, $learner, false);
    }

    public function downloadStudent(StandardLink $stdLink, User $learner): SymfonyResponse
    {
        return $this->singleStudentResponse($stdLink, $learner, true);
    }

    private function singleStudentResponse(StandardLink $stdLink, User $learner, bool $download): SymfonyResponse
    {
        $teacher = $this->actor();
        $this->authorizeClassTeacher($teacher, $stdLink);

        $schoolId = (int) $teacher->school_id;

        if ((int) $learner->school_id !== $schoolId || (int) $learner->usergroup_id !== 6) {
            abort(404);
        }

        try {
            $exam = $this->reports->resolveExam($schoolId, $stdLink);
            if (! $exam) {
                return back()->with('failmessage', 'No EOT exam found for this class.');
            }

            if (! $this->reports->studentIds($exam)->contains($learner->id)) {
                return back()->with('failmessage', 'This student has no marks for the report exam.');
            }

            // Inherit school template (null) — CTs do not pick formal/warm.
            $pdfContent = $this->reports->pdfForStudent($schoolId, $stdLink, $learner, null);

            $name = str_replace([' ', '/'], '_', $learner->name);
            $filename = "{$name}_report_card.pdf";

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($download ? 'attachment' : 'inline')."; filename=\"{$filename}\"",
            ]);
        } catch (\Throwable $e) {
            Log::error('teacher report card failed', [
                'stdLink' => $stdLink->id,
                'learner' => $learner->id,
                'schoolId' => $schoolId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('failmessage', 'Failed to generate report. Please try again.');
        }
    }

    private function authorizeClassTeacher(User $teacher, StandardLink $stdLink): void
    {
        if ((int) $stdLink->school_id !== (int) $teacher->school_id) {
            abort(403, 'You are not the class teacher for this class.');
        }

        if (! SiteHelper::isClassTeacherOfStandardLink(
            (int) $teacher->school_id,
            (int) $teacher->id,
            (int) $stdLink->id
        )) {
            abort(403, 'You are not the class teacher for this class.');
        }
    }

    private function actor(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
