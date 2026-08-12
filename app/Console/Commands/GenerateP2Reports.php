<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Academics\Exam;
use App\Services\StudentReportHelperService;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateP2Reports extends Command
{
    protected $signature = 'kabale:generate-p2-reports';
    protected $description = 'Batch-generate P.2 report card PDFs for Kabale school 104';

    public function handle(): int
    {
        $schoolId = 104;
        $examId = 21;
        $section = 46;

        $excludeNames = [
            'Atukunda Shivan Esther', 'Atukunda Shivan E',
            'Tayebwa Joas', 'Atukunda Jordan', 'Tumwebune Aminah', 'Yesutahinduka Aaron',
        ];

        $studentIds = DB::table('marks')
            ->join('users', 'marks.student_id', '=', 'users.id')
            ->where('marks.exam_id', $examId)
            ->whereNotIn('users.name', $excludeNames)
            ->distinct()
            ->pluck('marks.student_id');

        $this->info('Students to generate: ' . $studentIds->count());

        $outDir = storage_path('app/reports/p2_batch');
        if (!is_dir($outDir)) {
            mkdir($outDir, 0775, true);
        }

        $helper = app(StudentReportHelperService::class);
        $generated = 0;
        $errors = [];

        foreach ($studentIds as $sid) {
            try {
                $learner = User::find($sid);
                $exam = Exam::find($examId);
                $learner = $helper->learner($schoolId, $learner, $exam);
                $subjects = $helper->subjects($schoolId, $section, $learner, $exam);
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
                    ? (new \App\Services\ReportCardCommentService)->commentFor((int) $total, $standard->name, $learner->id, $exam->id)
                    : '';

                $pdf = Pdf::loadView('admin.marks.student-report', [
                    'subjects'            => $subjects,
                    'learner'             => $learner,
                    'controls'            => $controls,
                    'class_name'          => \App\Models\Section::find($section)->name,
                    'grading_system'      => \App\Models\Academics\SchoolGradingSystem::where('school_id', $schoolId)->get(),
                    'fees'                => collect(),
                    'nextTerm'            => \App\Models\AcademicTerm::where('school_id', $schoolId)->where('starts_on', '>', now())->first(),
                    'totalLearners'       => 1,
                    'myPos'               => 1,
                    'exams'               => $exams,
                    'marks'               => collect(),
                    'examsDone'           => 1,
                    'marksFromSubject'    => $marksFromSubject,
                    'total'               => $total,
                    'uniqueExamTypes'     => $uniqueExamTypes,
                    'grade'               => $grade,
                    'promotion'           => null,
                    'school'              => \App\Models\School::find(104),
                    'isNursery'           => $isNursery,
                    'nurseryAssessments'  => collect(),
                    'teacherComment'      => $teacherComment,
                ]);
                $pdf->setPaper('a4', 'portrait');

                $name = str_replace([' ', '/'], '_', $learner->name);
                $path = "{$outDir}/{$sid}_{$name}.pdf";
                file_put_contents($path, $pdf->output());
                $generated++;
            } catch (\Throwable $e) {
                $errors[] = "student {$sid}: " . $e->getMessage();
            }
        }

        $this->info("Generated: {$generated} PDFs");
        if ($errors) {
            $this->warn('Errors: ' . count($errors));
            foreach ($errors as $e) {
                $this->warn("  {$e}");
            }
        }
        $this->info("Output: {$outDir}");

        return 0;
    }
}