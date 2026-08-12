<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Services\ReportCardCommentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class ReportCardsController extends Controller
{
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
                }

                return $sl->eotExam !== null;
            })
            ->values();

        $eotKpis = self::computeEotKpis($schoolId, $selectedTerm);

        return view('admin.reports.cards', compact('stdLinks', 'terms', 'selectedTerm', 'eotKpis'));
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
        $schoolId = Auth::user()->school_id;

        $exam = $this->resolveExam($schoolId, $stdLink);
        if (!$exam) return back()->with('failmessage', 'No EOT exam found for this class.');

        $studentIds = $this->studentIds($exam);
        if ($studentIds->isEmpty()) return back()->with('failmessage', 'No students with marks.');

        $zipPath = tempnam(sys_get_temp_dir(), 'reports_');
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return back()->with('failmessage', 'Could not create zip file.');
        }

        $helper = app(\App\Services\StudentReportHelperService::class);
        $svc = new ReportCardCommentService;

        foreach ($studentIds as $sid) {
            $learner = \App\Models\User::find($sid);
            $pdfContent = $this->generatePdf($sid, $exam, $stdLink, $schoolId, $helper, $svc, $studentIds->count());
            $name = str_replace([' ', '/'], '_', $learner->name);
            $zip->addFromString("{$sid}_{$name}.pdf", $pdfContent);
        }
        $zip->close();

        $className = Section::find($stdLink->section_id)->name;
        $filename = str_replace(' ', '_', "report_cards_{$className}.zip");

        return response()->download($zipPath, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function downloadMerged(StandardLink $stdLink)
    {
        $schoolId = Auth::user()->school_id;

        $exam = $this->resolveExam($schoolId, $stdLink);
        if (!$exam) return back()->with('failmessage', 'No EOT exam found.');

        $studentIds = $this->studentIds($exam);
        if ($studentIds->isEmpty()) return back()->with('failmessage', 'No students with marks.');

        $helper = app(\App\Services\StudentReportHelperService::class);
        $svc = new ReportCardCommentService;
        $merger = new \iio\libmergepdf\Merger;

        foreach ($studentIds as $sid) {
            $merger->addRaw($this->generatePdf($sid, $exam, $stdLink, $schoolId, $helper, $svc, $studentIds->count()));
        }

        $merged = $merger->merge();
        $className = Section::find($stdLink->section_id)->name;
        $filename = str_replace(' ', '_', "report_cards_{$className}_merged.pdf");

        return response($merged, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
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

    private function generatePdf(int $sid, Exam $exam, StandardLink $stdLink, int $schoolId, $helper, ReportCardCommentService $svc, int $totalLearners): string
    {
        $learner = \App\Models\User::find($sid);
        $learner = $helper->learner($schoolId, $learner, $exam);
        $subjects = $helper->subjects($schoolId, $stdLink->section_id, $learner, $exam);
        $exams = $helper->exam($schoolId, $exam);

        $controls = ['SUBJECT', 'OUT OF'];
        $uniqueExamTypes = $exams->pluck('examType')->unique()->count();
        $marksFromSubject = [];
        foreach ($exams as $ex) {
            if (!in_array(strtoupper($ex->examType->code), $controls)) {
                $controls[] = strtoupper($ex->examType->code);
                $marksFromSubject[] = $ex;
                if ($uniqueExamTypes > 1) {
                    $controls = array_merge($controls, ['AVG']);
                }
            }
        }
        $controls = array_merge($controls, ['DIVISION', 'TEACHER', 'REMARK']);

        $standard = $learner->studentAcademicLatest?->standardLink?->standard;
        $isNursery = $standard && \App\Helpers\GradingHelper::levelTypeForStandard($standard) === 'nursery';

        $total = $learner->marks
            ? $learner->marks->filter(fn($m) => $m->exam?->examType?->contributes_to_report_total)->sum('marks')
            : 0;
        $grade = $helper->grade($learner, $exam);
        $teacherComment = $standard
            ? $svc->commentFor((int) $total, $standard->name, $learner->id, $exam->id)
            : '';

        $pdf = Pdf::loadView('admin.marks.student-report', [
            'subjects' => $subjects, 'learner' => $learner, 'controls' => $controls,
            'class_name' => Section::find($stdLink->section_id)->name,
            'grading_system' => \App\Models\Academics\SchoolGradingSystem::where('school_id', $schoolId)->get(),
            'fees' => collect(), 'nextTerm' => AcademicTerm::where('school_id', $schoolId)->where('starts_on', '>', now())->first(),
            'totalLearners' => $totalLearners, 'myPos' => 0,
            'exams' => $exams, 'marks' => collect(), 'examsDone' => $helper->examsDone($schoolId, $exam),
            'marksFromSubject' => $marksFromSubject, 'total' => $total,
            'uniqueExamTypes' => $uniqueExamTypes, 'grade' => $grade,
            'promotion' => null, 'school' => \App\Models\School::find($schoolId),
            'isNursery' => $isNursery, 'nurseryAssessments' => collect(),
            'teacherComment' => $teacherComment,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }
}