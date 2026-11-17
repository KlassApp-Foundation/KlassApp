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
        $this->raiseMemoryCeiling('1024M');

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
                throw new \RuntimeException('No exam set found for this class.');
            }

            $termId = $exam->academic_term_id ? (int) $exam->academic_term_id : null;
            $learners = $reports->reportLearners($schoolId, $stdLink, $termId);
            if ($learners->isEmpty()) {
                throw new \RuntimeException('No learners in this class.');
            }

            $className = Section::find($stdLink->section_id)->name ?? 'class';
            $safeClass = str_replace(' ', '_', $className);

            if ($generation->mode === 'merged') {
                // The pinned exam (when the caller passed one) chooses the term.
                // The cards themselves still come from pdfForStudent(), which
                // resolves that term the same way as the single-student download.
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
                foreach ($learners as $learner) {
                    $sid = (int) $learner->id;
                    $myPos = $positionMap[$sid] ?? 0;
                    $pdf = \App\Http\Controllers\Admin\ReportCardsController::generatePdf(
                        $sid, $exam, $stdLink, $schoolId, $helper, $svc, $learners->count(), $myPos
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

    /**
     * A class PDF needs room. Never lower a limit the caller already raised:
     * the suite runs this job inside a process that may already be above 1024M.
     */
    private function raiseMemoryCeiling(string $floor): void
    {
        $current = $this->memoryLimitBytes((string) ini_get('memory_limit'));
        $needed = $this->memoryLimitBytes($floor);
        if ($needed !== -1 && $current !== -1 && $current < $needed) {
            @ini_set('memory_limit', $floor);
        }
    }

    private function memoryLimitBytes(string $limit): int
    {
        $limit = trim($limit);
        if ($limit === '' || $limit === '-1') {
            return -1;
        }

        $unit = strtolower(substr($limit, -1));
        $number = (int) $limit;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $limit,
        };
    }

}
