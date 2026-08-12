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
use ZipArchive;

class ReportCardsController extends Controller
{
    public function index()
    {
        $schoolId = Auth::user()->school_id;
        $year = AcademicYear::where('school_id', $schoolId)->where('status', 1)->first();

        $terms = AcademicTerm::where('school_id', $schoolId)->get();
        $selectedTerm = request('term', $terms->first()?->id);

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

        return view('admin.reports.cards', compact('stdLinks', 'terms', 'selectedTerm'));
    }

    public function downloadClass(StandardLink $stdLink)
    {
        $schoolId = Auth::user()->school_id;

        $exam = Exam::where('school_id', $schoolId)
            ->where('section_id', $stdLink->section_id)
            ->where('standard_id', $stdLink->standard_id)
            ->whereHas('examType', fn($q) => $q->where('contributes_to_report_total', 1))
            ->latest()
            ->first();

        if (!$exam) {
            return back()->with('failmessage', 'No EOT exam found for this class.');
        }

        $studentIds = \App\Models\Academics\Marks::where('exam_id', $exam->id)
            ->distinct('student_id')
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return back()->with('failmessage', 'No students with marks for this exam.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'reports_');
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return back()->with('failmessage', 'Could not create zip file.');
        }

        $helper = app(\App\Services\StudentReportHelperService::class);
        $commentSvc = new ReportCardCommentService;

        foreach ($studentIds as $sid) {
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
                ? $commentSvc->commentFor((int) $total, $standard->name, $learner->id, $exam->id)
                : '';

            $pdf = Pdf::loadView('admin.marks.student-report', [
                'subjects' => $subjects, 'learner' => $learner, 'controls' => $controls,
                'class_name' => Section::find($stdLink->section_id)->name,
                'grading_system' => \App\Models\Academics\SchoolGradingSystem::where('school_id', $schoolId)->get(),
                'fees' => collect(), 'nextTerm' => AcademicTerm::where('school_id', $schoolId)->where('starts_on', '>', now())->first(),
                'totalLearners' => $studentIds->count(), 'myPos' => 0,
                'exams' => $exams, 'marks' => collect(), 'examsDone' => $helper->examsDone($schoolId, $exam),
                'marksFromSubject' => $marksFromSubject, 'total' => $total,
                'uniqueExamTypes' => $uniqueExamTypes, 'grade' => $grade,
                'promotion' => null, 'school' => \App\Models\School::find($schoolId),
                'isNursery' => $isNursery, 'nurseryAssessments' => collect(),
                'teacherComment' => $teacherComment,
            ]);
            $pdf->setPaper('a4', 'portrait');

            $name = str_replace([' ', '/'], '_', $learner->name);
            $zip->addFromString("{$sid}_{$name}.pdf", $pdf->output());
        }

        $zip->close();

        $className = Section::find($stdLink->section_id)->name;
        $filename = str_replace(' ', '_', "report_cards_{$className}.zip");

        return response()->download($zipPath, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}