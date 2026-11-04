<?php

namespace App\Jobs;

use App\Models\Academics\Exam;
use App\Models\ReportGeneration;
use App\Models\Section;
use App\Models\StandardLink;
use App\Services\ReportCardCommentService;
use App\Services\StudentReportHelperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ZipArchive;

class GenerateClassReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries = 1;

    public function __construct(public int $generationId, public ?int $examId = null)
    {
    }

    public function handle(): void
    {
        @ini_set('memory_limit', '1024M');

        $generation = ReportGeneration::find($this->generationId);
        if (!$generation) {
            return;
        }

        $generation->update(['status' => 'processing']);

        try {
            $schoolId = $generation->school_id;
            $stdLink = StandardLink::find($generation->standard_link_id);
            if (!$stdLink) {
                throw new \RuntimeException('Class link not found.');
            }

            // An explicit exam pin (seeding a past term) wins. Otherwise the
            // term stored on the generation is the term the admin selected.
            // That is never "the newest created exam".
            $reports = app(\App\Services\StudentReportCardService::class);
            $exam = $this->examId
                ? Exam::where('school_id', $schoolId)->whereKey($this->examId)->first()
                : $reports->resolveExam($schoolId, $stdLink, $generation->academic_term_id);

            if (!$exam) {
                throw new \RuntimeException('No EOT exam found for this class.');
            }

            $studentIds = \App\Models\Academics\Marks::where('exam_id', $exam->id)
                ->distinct('student_id')
                ->orderBy('student_id')
                ->pluck('student_id');

            if ($studentIds->isEmpty()) {
                throw new \RuntimeException('No students with marks.');
            }

            $className = Section::find($stdLink->section_id)->name ?? 'class';
            $safeClass = str_replace(' ', '_', $className);

            if ($generation->mode === 'merged') {
                // The pinned exam (when the caller passed one) chooses the term.
                // The cards themselves still come from pdfForStudent(), which
                // resolves that term the same way as the single-student download.
                $termId = $exam->academic_term_id ? (int) $exam->academic_term_id : null;
                $learners = $reports->reportLearners($schoolId, $stdLink, $termId);
                if ($learners->isEmpty()) {
                    throw new \RuntimeException('No students with marks.');
                }
                // Same pdfForStudent() view and data as the single-student card,
                // so each page keeps that student's own class and stream fees.
                $content = $reports->mergedPdfForLearners($schoolId, $stdLink, $learners, $termId);
                $fileName = 'report_cards_'.$safeClass.'_'.$generation->id.'.pdf';
                $filePath = 'reports/'.$schoolId.'/'.$fileName;
                $dir = storage_path('app/reports/'.$schoolId);
                if (! is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                file_put_contents(storage_path('app/'.$filePath), $content);

                $generation->update([
                    'status' => 'completed',
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'completed_at' => now(),
                ]);

                return;
            } else {
                $helper = app(StudentReportHelperService::class);
                $svc = new ReportCardCommentService;
                $positionMap = $reports->computePositionMap($exam, $schoolId);
                $dir = storage_path('app/reports');
                if (! is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }

                $zipPath = tempnam(sys_get_temp_dir(), 'reports_');
                $zip = new ZipArchive;
                if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                    throw new \RuntimeException('Could not create zip file.');
                }
                foreach ($studentIds as $sid) {
                    $learner = \App\Models\User::find($sid);
                    $myPos = $positionMap[$sid] ?? 0;
                    $pdf = \App\Http\Controllers\Admin\ReportCardsController::generatePdf(
                        $sid, $exam, $stdLink, $schoolId, $helper, $svc, $studentIds->count(), $myPos
                    );
                    $name = $learner->displayNameFilenameSlug((string) $sid);
                    $zip->addFromString("{$sid}_{$name}.pdf", $pdf);
                }
                $zip->close();
                $fileName = "report_cards_{$safeClass}.zip";
                $content = file_get_contents($zipPath);
                @unlink($zipPath);
            }

            $filePath = 'reports/' . $fileName;
            file_put_contents(storage_path('app/' . $filePath), $content);

            $generation->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_name' => $fileName,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $generation->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            throw $e;
        }
    }

}
