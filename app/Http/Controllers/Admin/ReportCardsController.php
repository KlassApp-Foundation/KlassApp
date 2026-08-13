<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Services\ReportCardCommentService;
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
     */
    public const TEMPLATES = [
        'formal' => ['label' => 'Formal', 'view' => 'admin.marks.report-templates.formal'],
        'warm'   => ['label' => 'Warm',   'view' => 'admin.marks.report-templates.warm'],
    ];

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
    public static function computeEotKpis(int $schoolId, ?int $academicTermId = null): array
    {
        // --- Per class: average of per-student EOT totals, grouped by section ---
        $perClass = DB::table('marks as m')
            ->join('exams as e', 'm.exam_id', '=', 'e.id')
            ->join('exam_types as et', 'e.exam_type_id', '=', 'et.id')
            ->where('m.school_id', $schoolId)
            ->where('et.contributes_to_report_total', 1)
            ->when($academicTermId, fn($q) => $q->where('e.academic_term_id', $academicTermId))
            ->select('m.section_id', DB::raw('SUM(m.marks) as total'))
            ->groupBy('m.student_id', 'm.section_id');

        $perClass = DB::table(DB::raw("({$perClass->toSql()}) as student_totals"))
            ->mergeBindings($perClass)
            ->join('sections as s', 'student_totals.section_id', '=', 's.id')
            ->select('s.name as label', DB::raw('ROUND(AVG(student_totals.total), 1) as value'))
            ->groupBy('s.name', 'student_totals.section_id')
            ->orderBy('s.name')
            ->get()
            ->toArray();

        // --- Per subject: average mark per subject ---
        $perSubject = DB::table('marks as m')
            ->join('exams as e', 'm.exam_id', '=', 'e.id')
            ->join('exam_types as et', 'e.exam_type_id', '=', 'et.id')
            ->join('subjects as sub', 'm.subject_id', '=', 'sub.id')
            ->where('m.school_id', $schoolId)
            ->where('et.contributes_to_report_total', 1)
            ->when($academicTermId, fn($q) => $q->where('e.academic_term_id', $academicTermId))
            ->select('sub.name as label', DB::raw('ROUND(AVG(m.marks), 1) as value'))
            ->groupBy('sub.name', 'm.subject_id')
            ->orderBy('sub.name')
            ->get()
            ->toArray();

        // --- Per gender: average of per-student EOT totals, split by gender ---
        $studentTotalsForGender = DB::table('marks as m')
            ->join('exams as e', 'm.exam_id', '=', 'e.id')
            ->join('exam_types as et', 'e.exam_type_id', '=', 'et.id')
            ->where('m.school_id', $schoolId)
            ->where('et.contributes_to_report_total', 1)
            ->when($academicTermId, fn($q) => $q->where('e.academic_term_id', $academicTermId))
            ->select('m.student_id', DB::raw('SUM(m.marks) as total'))
            ->groupBy('m.student_id');

        $perGender = DB::table(DB::raw("({$studentTotalsForGender->toSql()}) as student_totals"))
            ->mergeBindings($studentTotalsForGender)
            ->join('users as u', 'student_totals.student_id', '=', 'u.id')
            ->join('userprofiles as up', 'u.id', '=', 'up.user_id')
            ->whereIn('up.gender', ['male', 'female'])
            ->select(DB::raw("CASE WHEN up.gender = 'male' THEN 'Male' ELSE 'Female' END as label"), DB::raw('ROUND(AVG(student_totals.total), 1) as value'))
            ->groupBy('up.gender')
            ->orderBy('up.gender')
            ->get()
            ->toArray();

        return [
            'perClass'   => $perClass,
            'perSubject' => $perSubject,
            'perGender'  => $perGender,
        ];
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

            $exam = $this->resolveExam($schoolId, $stdLink);
            if (!$exam) return back()->with('failmessage', 'No EOT exam found for this class.');

            $allStudentIds = $this->studentIds($exam);
            $helper = app(\App\Services\StudentReportHelperService::class);
            $svc = new ReportCardCommentService;
            $positionMap = $this->computePositionMap($exam, $schoolId);
            $myPos = $positionMap[$learner->id] ?? 0;
            $pdfContent = $this->generatePdf($learner->id, $exam, $stdLink, $schoolId, $helper, $svc, $allStudentIds->count(), $myPos);

            $name = str_replace([' ', '/'], '_', $learner->name);
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
        return Exam::where('school_id', $schoolId)
            ->where('section_id', $stdLink->section_id)
            ->where('standard_id', $stdLink->standard_id)
            ->whereHas('examType', fn($q) => $q->where('contributes_to_report_total', 1))
            ->latest()
            ->first();
    }

    private function studentIds(Exam $exam)
    {
        return \App\Models\Academics\Marks::where('exam_id', $exam->id)
            ->distinct('student_id')
            ->orderBy('student_id')
            ->pluck('student_id');
    }

    private function computePositionMap(Exam $exam, int $schoolId): array
    {
        $studentIds = $this->studentIds($exam);
        if ($studentIds->isEmpty()) return [];

        $helper = app(\App\Services\StudentReportHelperService::class);
        $scopedExamIds = Exam::where('school_id', $schoolId)
            ->where('section_id', $exam->section_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('academic_term_id', $exam->academic_term_id)
            ->pluck('id');

        $learners = \App\Models\User::whereIn('id', $studentIds)
            ->with(['marks' => fn($q) => $q->whereIn('exam_id', $scopedExamIds)->with('exam.examType')])
            ->where('usergroup_id', 6)
            ->get();

        $learners = $helper->position($learners, $exam);

        $map = [];
        foreach ($learners as $l) {
            $map[$l->id] = $l->position;
        }
        return $map;
    }

    public static function generatePdf(int $sid, Exam $exam, StandardLink $stdLink, int $schoolId, $helper, ReportCardCommentService $svc, int $totalLearners, int $myPos = 0, ?string $templateKey = null): string
    {
        $learner = \App\Models\User::find($sid);
        $learner = $helper->learner($schoolId, $learner, $exam);
        $subjects = $helper->subjects($schoolId, $stdLink->section_id, $learner, $exam);
        $subjects = \App\Models\Subject::sortByReportOrder($subjects);
        $exams = $helper->exam($schoolId, $exam);

        $midExams = $exams->filter(fn($e) => $e->examType->code === 'MID')->sortBy('scheduled_at')->values();
        $eotExams = $exams->filter(fn($e) => $e->examType->code !== 'MID')->values();
        $allExamColumns = $midExams->merge($eotExams);

        $controls = ['SUBJECT', 'OUT OF'];
        foreach ($midExams as $ex) {
            $controls[] = strtoupper($ex->scheduled_at->format('M')) . ' MID';
        }
        foreach ($eotExams as $ex) {
            $controls[] = 'EOT';
        }
        $controls = array_merge($controls, ['DIVISION', 'TEACHER', 'REMARK']);

        $standard = $learner->studentAcademicLatest?->standardLink?->standard;
        $isNursery = $standard && \App\Helpers\GradingHelper::levelTypeForStandard($standard) === 'nursery';

        $standardName = $standard?->name ?? '';
        $showAgg = !$isNursery && !in_array($standardName, ['primary_lower'], true);

        $gradingSystem = \App\Models\Academics\SchoolGradingSystem::where('school_id', $schoolId)
            ->where('standard_id', $stdLink->standard_id)
            ->orderBy('min_score', 'desc')
            ->get();

        $divisionScale = static function (int $points): string {
            if ($points <= 12) { return '1st'; }
            if ($points <= 24) { return '2nd'; }
            if ($points <= 28) { return '3rd'; }
            if ($points <= 32) { return '4th'; }
            return 'U';
        };

        $aggregatePoints = static function ($learner, $exam, $subjects, $gradingSystem) {
            $sum = 0;
            $counted = 0;
            foreach ($subjects as $subject) {
                if ($learner->marks->where('subject_id', $subject->id)->isEmpty()) {
                    continue;
                }
                $mark = $learner->marks->where('subject_id', $subject->id)->firstWhere('exam_id', $exam->id);
                if ($mark && $mark->marks !== null) {
                    $g = $gradingSystem->first(fn($gs) => $gs->min_score <= (float) $mark->marks && $gs->max_score >= (float) $mark->marks);
                    if ($g && $g->points !== null) {
                        $sum += (int) $g->points;
                        $counted++;
                    }
                }
            }
            return $counted ? ['points' => $sum, 'counted' => $counted] : null;
        };

        $midStats = [];
        foreach ($midExams as $midExam) {
            $total = (int) round($learner->marks->filter(fn($m) => $m->exam_id === $midExam->id)->sum('marks'));
            $points = $aggregatePoints($learner, $midExam, $subjects, $gradingSystem);
            $studentIds = \App\Models\Academics\Marks::where('exam_id', $midExam->id)->distinct('student_id')->pluck('student_id')->all();
            $ranked = [];
            foreach ($studentIds as $sid) {
                $ranked[$sid] = (int) round(\App\Models\Academics\Marks::where('student_id', $sid)->where('exam_id', $midExam->id)->sum('marks'));
            }
            arsort($ranked);
            $pos = 0;
            foreach (array_keys($ranked) as $i => $sid) {
                if ($sid === $learner->id) {
                    $pos = $i + 1;
                    break;
                }
            }
            $midStats[$midExam->id] = [
                'total' => $total,
                'pos' => $pos,
                'of' => count($ranked),
                'division' => $points ? $divisionScale($points['points']) : '-',
            ];
        }

        $firstEot = $eotExams->first();
        $eotPoints = $firstEot ? $aggregatePoints($learner, $firstEot, $subjects, $gradingSystem) : null;
        $eotDivision = $eotPoints ? $divisionScale($eotPoints['points']) : '-';

        $streamName = $learner->studentAcademicLatest?->standardLink?->stream ?? null;
        $streamPos = null;
        $streamTotal = null;
        if ($streamName && $firstEot) {
            $streamIds = [];
            $streamRows = DB::table('student_academics')
                ->join('standards_link', 'standards_link.id', '=', 'student_academics.standardLink_id')
                ->where('standards_link.school_id', $schoolId)
                ->where('standards_link.stream', $streamName)
                ->where('student_academics.academic_year_id', $exam->academic_year_id)
                ->whereNull('student_academics.deleted_at')
                ->pluck('student_academics.user_id')
                ->all();
            $ranked = [];
            foreach ($streamRows as $sid) {
                $ranked[$sid] = (int) round(\App\Models\Academics\Marks::where('student_id', $sid)->where('exam_id', $firstEot->id)->sum('marks'));
            }
            arsort($ranked);
            $streamPos = 0;
            foreach (array_keys($ranked) as $i => $sid) {
                if ($sid === $learner->id) {
                    $streamPos = $i + 1;
                    break;
                }
            }
            $streamTotal = count($ranked);
        }

        $total = $learner->marks
            ? $learner->marks->filter(fn($m) => $m->exam?->examType?->contributes_to_report_total)->sum('marks')
            : 0;
        $examinedSubjectCount = $subjects->filter(fn($s) => $learner->marks->where('subject_id', $s->id)->isNotEmpty())->count();
        $grade = $helper->grade($learner, $exam);
        $teacherComment = $standard
            ? $svc->commentFor((int) $total, $standard->name, $learner->id, $exam->id)
            : '';
        $headTeacherComment = $standard
            ? $svc->headTeacherCommentFor((int) $total, $standard->name, $learner->id, $exam->id, $teacherComment)
            : '';

        $school = \App\Models\School::find($schoolId);
        $view = self::TEMPLATES[$templateKey ?? $school->report_template ?? ''] ?? self::TEMPLATES['formal'];
        $logoPath = self::resolveLogoPath($schoolId);

        $pdf = Pdf::loadView($view['view'], [
            'subjects' => $subjects, 'learner' => $learner, 'controls' => $controls,
            'class_name' => Section::find($stdLink->section_id)->name,
            'grading_system' => $gradingSystem,
            'fees' => collect(), 'nextTerm' => AcademicTerm::where('school_id', $schoolId)->where('starts_on', '>', now())->first(),
            'totalLearners' => $totalLearners, 'myPos' => $myPos,
            'allExamColumns' => $allExamColumns, 'midExams' => $midExams, 'eotExams' => $eotExams,
            'midCount' => $midExams->count(), 'eotCount' => $eotExams->count(),
            'stdLink' => $stdLink,
            'total' => $total, 'grade' => $grade, 'examinedSubjectCount' => $examinedSubjectCount,
            'school' => $school,
            'isNursery' => $isNursery, 'nurseryAssessments' => collect(),
            'teacherComment' => $teacherComment,
            'headTeacherComment' => $headTeacherComment,
            'showAgg' => $showAgg,
            'midStats' => $midStats,
            'eotDivision' => $eotDivision,
            'streamName' => $streamName,
            'streamPos' => $streamPos,
            'streamTotal' => $streamTotal,
            'logoPath' => $logoPath,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    public function previewTemplate(string $template)
    {
        abort_unless(isset(self::TEMPLATES[$template]), 404);

        $schoolId = Auth::user()->school_id;

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
        $svc = app(\App\Services\ReportCardCommentService::class);
        $totalLearners = \App\Models\Academics\Marks::where('exam_id', $exam->id)->distinct('student_id')->count();

        $pdf = self::generatePdf($learner->id, $exam, $stdLink, $schoolId, $helper, $svc, $totalLearners, 0, $template);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="preview-' . $template . '.pdf"');
    }

    /**
     * Resolve the school's uploaded logo to a local filesystem path for
     * DomPDF (which needs a real path, not a public URL). Uses the same
     * SchoolDetail (meta_key=school_logo) record every other part of the
     * app reads via Auth::user()->SchoolLogoPath — not a hardcoded asset.
     * Returns null (report card renders without a logo) if none uploaded
     * or the stored file is missing from disk.
     */
    private static function resolveLogoPath(int $schoolId): ?string
    {
        // SchoolObserver seeds every school with a placeholder school_logo
        // row (meta_value='-') at creation; the real upload flow always
        // updateOrCreate()s a single row per school+meta_key, but order by
        // most-recent and explicitly skip the '-' sentinel defensively.
        $meta = \App\Models\SchoolDetail::where('school_id', $schoolId)
            ->where('meta_key', 'school_logo')
            ->latest('id')
            ->first();

        if (!$meta || !$meta->meta_value || $meta->meta_value === '-') {
            return null;
        }

        $path = \Storage::disk('public')->path($meta->meta_value);

        return file_exists($path) ? $path : null;
    }
}