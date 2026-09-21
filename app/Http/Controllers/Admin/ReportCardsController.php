<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Services\ReportCardCommentService;
use App\Services\StudentReportCardService;
use App\Exports\CombinedMarksheetExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class ReportCardsController extends Controller
{
    /**
     * Available report-card templates: key => [label, Blade view].
     * Canonical registry lives on StudentReportCardService — keep this alias
     * so existing Blade / admin callers stay unchanged.
     */
    public const TEMPLATES = StudentReportCardService::TEMPLATES;

    public function index()
    {
        $schoolId = Auth::user()->school_id;

        $terms = AcademicTerm::where('school_id', $schoolId)->orderBy('starts_on')->get();
        $selectedTerm = request('term', $terms->firstWhere('status', 'current')?->id ?? $terms->first()?->id);

        $stdLinks = StandardLink::where('school_id', $schoolId)
            ->with(['section', 'standard'])
            ->get()
            ->filter(function ($sl) use ($schoolId, $selectedTerm) {
                $sl->eotExam = Exam::where('school_id', $schoolId)
                    ->where('section_id', $sl->section_id)
                    ->where('standard_id', $sl->standard_id)
                    ->where('academic_term_id', $selectedTerm)
                    ->whereHas('examType', fn($q) => $q->where('contributes_to_report_total', 1))
                    ->first();

                if ($sl->eotExam) {
                    $sl->studentCount = \App\Models\Academics\Marks::where('exam_id', $sl->eotExam->id)
                        ->distinct('student_id')
                        ->count('student_id');

                    $sl->students = \App\Models\User::whereIn('id',
                        \App\Models\Academics\Marks::where('exam_id', $sl->eotExam->id)
                            ->distinct('student_id')
                            ->pluck('student_id')
                    )->orderBy('name')->get(['id', 'name']);
                }

                return $sl->eotExam !== null;
            })
            ->values();

        $eotKpis = self::computeEotKpis($schoolId, $selectedTerm);

        $recentGenerations = \App\Models\ReportGeneration::where('school_id', $schoolId)
            ->latest()
            ->take(10)
            ->get();

        $school = \App\Models\School::find($schoolId);
        $reportTemplate = $school->report_template ?? 'formal';

        return view('admin.reports.cards', compact('stdLinks', 'terms', 'selectedTerm', 'eotKpis', 'recentGenerations', 'reportTemplate', 'school'));
    }

    public function updateTemplate(Request $request)
    {
        $schoolId = Auth::user()->school_id;

        $request->validate([
            'report_template' => 'required|string|in:' . implode(',', array_keys(self::TEMPLATES)),
        ]);

        \App\Models\School::where('id', $schoolId)->update([
            'report_template' => $request->report_template,
        ]);

        return back()->with('successmessage', 'Report card template updated to ' . self::TEMPLATES[$request->report_template]['label'] . '.');
    }

    /**
     * Compute EOT mark KPIs for bar chart display: per class, per subject, per gender.
     *
     * @return array{perClass: array, perSubject: array, perGender: array}
     */
    /**
     * EOT KPIs for the dashboard + report-cards page.
     *
     * Reads the materialized `report_kpi_snapshots` table (rebuilt by
     * `php artisan report-kpis:rebuild`) and falls back to the original live
     * aggregate when the snapshot is missing or older than
     * config('report_kpis.stale_after_minutes') — so a stale snapshot can never
     * show wrong numbers. The aggregate itself now lives in
     * App\Services\ReportKpiSnapshotService::computeLive().
     */
    public static function computeEotKpis(int $schoolId, ?int $academicTermId = null): array
    {
        return app(\App\Services\ReportKpiSnapshotService::class)->kpis($schoolId, $academicTermId);
    }

    public function downloadClass(StandardLink $stdLink)
    {
        return $this->dispatchGeneration($stdLink, 'zip');
    }

    public function downloadMerged(StandardLink $stdLink)
    {
        return $this->dispatchGeneration($stdLink, 'merged');
    }

    public function combinedMarksheet(StandardLink $stdLink)
    {
        $schoolId = Auth::user()->school_id;

        if ($stdLink->school_id !== $schoolId) {
            abort(403);
        }

        $term = AcademicTerm::where('school_id', $schoolId)
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

    private function dispatchGeneration(StandardLink $stdLink, string $mode)
    {
        $schoolId = Auth::user()->school_id;

        $exam = $this->resolveExam($schoolId, $stdLink);
        if (!$exam) return back()->with('failmessage', 'No EOT exam found for this class.');

        $studentIds = $this->studentIds($exam);
        if ($studentIds->isEmpty()) return back()->with('failmessage', 'No students with marks.');

        $className = Section::find($stdLink->section_id)->name ?? 'class';

        $generation = \App\Models\ReportGeneration::create([
            'school_id' => $schoolId,
            'standard_link_id' => $stdLink->id,
            'class_name' => $className,
            'mode' => $mode,
            'status' => 'pending',
            'requested_by' => Auth::id(),
        ]);

        \App\Jobs\GenerateClassReportsJob::dispatch($generation->id);

        return redirect()->route('admin.reports.cards.index')
            ->with('successmessage', "Generating {$className} " . ($mode === 'merged' ? 'merged PDF' : 'zip') . ". You'll see it in Recent Generations when ready.");
    }

    public function downloadGeneration(\App\Models\ReportGeneration $generation)
    {
        if ($generation->school_id !== Auth::user()->school_id) {
            abort(403);
        }

        if ($generation->status !== 'completed' || !$generation->file_path) {
            return back()->with('failmessage', 'This report is not ready yet.');
        }

        $path = storage_path('app/' . $generation->file_path);
        if (!file_exists($path)) {
            return back()->with('failmessage', 'Report file no longer exists.');
        }

        return response()->download($path, $generation->file_name);
    }

    public function previewStudent(StandardLink $stdLink, \App\Models\User $learner)
    {
        return $this->singleStudentResponse($stdLink, $learner, false);
    }

    public function downloadStudent(StandardLink $stdLink, \App\Models\User $learner)
    {
        return $this->singleStudentResponse($stdLink, $learner, true);
    }

    public function missingMarksPdf()
    {
        $schoolId = Auth::user()?->school_id;
        if (!$schoolId) return back()->with('failmessage', 'No school linked.');

        $academicTermId = request('term', AcademicTerm::where('school_id', $schoolId)
            ->where('status', 'current')->value('id'));

        $eotIds = Exam::where('school_id', $schoolId)
            ->where('academic_term_id', $academicTermId)
            ->whereHas('examType', fn($q) => $q->where('code', 'EOT'))
            ->pluck('id');

        $stdLinks = StandardLink::where('school_id', $schoolId)
            ->with(['section', 'standard'])
            ->whereHas('standard', fn($q) => $q->whereNot('name', 'nursery'))
            ->get();

        $missing = [];

        foreach ($stdLinks as $sl) {
            $exam = $this->resolveExam($schoolId, $sl);
            if (!$exam) continue;

            $studentIds = $this->studentIds($exam);
            $students = \App\Models\User::whereIn('id', $studentIds)
                ->where('status', 'active')
                ->where('name', 'regexp', '^[^0-9]+$')
                ->whereNotIn('id', function ($q) use ($eotIds) {
                    $q->select('student_id')->from('marks')
                        ->whereIn('exam_id', $eotIds)->distinct();
                })
                ->orderBy('name')
                ->get(['id', 'name']);

            if ($students->isNotEmpty()) {
                $missing[] = [
                    'class' => $sl->section->name,
                    'standard' => $sl->standard->name,
                    'count' => $students->count(),
                    'students' => $students,
                ];
            }
        }

        $html = view('admin.reports.missing-marks', [
            'missing' => $missing,
            'school' => \App\Models\School::find($schoolId),
            'academicTermId' => $academicTermId,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('missing_marks_report.pdf');
    }

    private function singleStudentResponse(StandardLink $stdLink, \App\Models\User $learner, bool $download)
    {
        try {
            $schoolId = Auth::user()?->school_id;
            if (!$schoolId) return back()->with('failmessage', 'Your account is not linked to a school.');

            $reports = app(StudentReportCardService::class);
            $exam = $reports->resolveExam($schoolId, $stdLink);
            if (!$exam) return back()->with('failmessage', 'No EOT exam found for this class.');

            $pdfContent = $reports->pdfForStudent($schoolId, $stdLink, $learner);

            $name = $learner->displayNameFilenameSlug('student');
            $filename = "{$name}_report_card.pdf";

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($download ? 'attachment' : 'inline') . "; filename=\"{$filename}\"",
            ]);
        } catch (\Throwable $e) {
            \Log::error('singleStudentResponse failed', [
                'stdLink' => $stdLink->id,
                'learner' => $learner->id,
                'schoolId' => Auth::user()?->school_id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('failmessage', 'Failed to generate report. Please try again.');
        }
    }

    private function resolveExam(int $schoolId, StandardLink $stdLink): ?Exam
    {
        return app(StudentReportCardService::class)->resolveExam($schoolId, $stdLink);
    }

    private function studentIds(Exam $exam)
    {
        return app(StudentReportCardService::class)->studentIds($exam);
    }

    private function computePositionMap(Exam $exam, int $schoolId): array
    {
        return app(StudentReportCardService::class)->computePositionMap($exam, $schoolId);
    }

    /**
     * Backward-compatible entry point for jobs/commands. Real implementation:
     * StudentReportCardService::generatePdf().
     */
    public static function generatePdf(int $sid, Exam $exam, StandardLink $stdLink, int $schoolId, $helper, ReportCardCommentService $svc, int $totalLearners, int $myPos = 0, ?string $templateKey = null): string
    {
        return app(StudentReportCardService::class)->generatePdf(
            $sid,
            $exam,
            $stdLink,
            $schoolId,
            $helper,
            $svc,
            $totalLearners,
            $myPos,
            $templateKey
        );
    }

    public function previewTemplate(string $template)
    {
        abort_unless(isset(self::TEMPLATES[$template]), 404);

        $schoolId = Auth::user()->school_id;
        $reports = app(StudentReportCardService::class);

        $exam = Exam::where('school_id', $schoolId)
            ->whereHas('examType', fn ($q) => $q->where('contributes_to_report_total', 1))
            ->latest()
            ->first();

        if (!$exam) {
            return back()->with('failmessage', 'No exams with report-total marks yet — nothing to preview.');
        }

        $stdLink = StandardLink::where('section_id', $exam->section_id)->where('standard_id', $exam->standard_id)->first();

        $learner = \App\Models\User::where('usergroup_id', 6)
            ->whereHas('marks', fn ($q) => $q->where('exam_id', $exam->id))
            ->first();

        if (!$stdLink || !$learner) {
            return back()->with('failmessage', 'No students with marks for the latest exam — nothing to preview.');
        }

        $helper = app(\App\Services\StudentReportHelperService::class);
        $svc = app(ReportCardCommentService::class);
        $totalLearners = \App\Models\Academics\Marks::where('exam_id', $exam->id)->distinct('student_id')->count();
        $positionMap = $reports->computePositionMap($exam, $schoolId);
        $myPos = $positionMap[$learner->id] ?? 0;

        $pdf = $reports->generatePdf($learner->id, $exam, $stdLink, $schoolId, $helper, $svc, $totalLearners, $myPos, $template);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="preview-' . $template . '.pdf"');
    }
}
