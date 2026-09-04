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

                $stdLink = \App\Models\StandardLink::where('school_id', $schoolId)
                    ->where('section_id', $section)
                    ->where('standard_id', $exam->standard_id)
                    ->first();

                $pdfContent = \App\Http\Controllers\Admin\ReportCardsController::generatePdf(
                    $learner->id, $exam, $stdLink, $schoolId, $helper, new \App\Services\ReportCardCommentService, 0, 0
                );

                $name = $learner->displayNameFilenameSlug((string) $sid);
                $path = "{$outDir}/{$sid}_{$name}.pdf";
                file_put_contents($path, $pdfContent);
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