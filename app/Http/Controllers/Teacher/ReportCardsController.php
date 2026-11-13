<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\ReportGeneration;
use App\Models\StandardLink;
use App\Models\User;
use App\Services\ClassReportCardAuthorization;
use App\Services\ClassReportCardDownload;
use App\Services\ExamAuthorization;
use App\Services\StudentReportCardService;
use App\Support\ClassListOrder;
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
        $year = SiteHelper::getAcademicYear($schoolId);
        $sectionIds = $year
            ? app(ExamAuthorization::class)->sectionIdsForClassTeacher($teacher, $schoolId, (int) $year->id)
            : [];

        // The class head's own classes (#1271), in class-level order (#1269).
        $stdLinks = ClassListOrder::sortLinks(
            StandardLink::query()
                ->where('school_id', $schoolId)
                ->when($year, fn ($query) => $query->where('academic_year_id', $year->id))
                ->whereIn('section_id', $sectionIds)
                ->with(['section', 'standard'])
                ->get()
        );

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
                'examName' => trim((string) ($exam?->examType?->name ?? '')),
                'setNames' => $this->reports->examSetNames($schoolId, $sl),
                'studentCount' => $studentCount,
                'subjectsFilled' => $fill['filled'] ?? 0,
                'subjectsTotal' => $fill['total'] ?? 0,
            ];
        });

        $compositionLine = app(\App\Services\ReportCards\EndOfTermCompositionLine::class)
            ->lineFor($schoolId, true, null);

        return view('teacher.reports.cards.index', [
            'rows' => $rows,
            'compositionLine' => $compositionLine,
        ]);
    }

    public function show(StandardLink $stdLink): View
    {
        $teacher = $this->actor();
        $this->authorizeClassTeacher($teacher, $stdLink);

        $schoolId = (int) $teacher->school_id;
        $exam = $this->reports->resolveExam($schoolId, $stdLink);
        if ($exam !== null) {
            $exam->loadMissing('examType');
        }
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

        $classGeneration = ReportGeneration::query()
            ->where('school_id', $schoolId)
            ->where('standard_link_id', $stdLink->id)
            ->where('mode', 'merged')
            ->latest('id')
            ->first();

        return view('teacher.reports.cards.show', [
            'stdLink' => $stdLink,
            'students' => $students,
            'hasExam' => $exam !== null,
            'examName' => trim((string) ($exam?->examType?->name ?? '')),
            'compositionLine' => app(\App\Services\ReportCards\EndOfTermCompositionLine::class)
                ->lineFor($schoolId, $exam !== null, $exam?->id),
            'subjectsFilled' => $fill['filled'] ?? 0,
            'subjectsTotal' => $fill['total'] ?? 0,
            'classGeneration' => $classGeneration,
        ]);
    }

    public function downloadClassPdf(StandardLink $stdLink): SymfonyResponse
    {
        return app(ClassReportCardDownload::class)->download(
            $this->actor(),
            $stdLink,
            route('teacher.reports.cards.show', $stdLink)
        );
    }

    public function downloadGeneration(ReportGeneration $generation): SymfonyResponse
    {
        return app(ClassReportCardDownload::class)->fileResponse($this->actor(), $generation);
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
                return back()->with('failmessage', 'No exam set found for this class.');
            }

            if (! $this->reports->studentIds($exam)->contains($learner->id)) {
                return back()->with('failmessage', 'This student has no marks for the report exam.');
            }

            // Inherit school template (null) — CTs do not pick formal/warm.
            $pdfContent = $this->reports->pdfForStudent($schoolId, $stdLink, $learner, null);

            $name = $learner->displayNameFilenameSlug('student');
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
        app(ClassReportCardAuthorization::class)->authorize($teacher, $stdLink);
    }

    private function actor(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
