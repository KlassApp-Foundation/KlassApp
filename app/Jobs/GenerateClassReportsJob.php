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

    public function __construct(public int $generationId)
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

            $exam = Exam::where('school_id', $schoolId)
                ->where('section_id', $stdLink->section_id)
                ->where('standard_id', $stdLink->standard_id)
                ->whereHas('examType', fn($q) => $q->where('contributes_to_report_total', 1))
                ->latest()
                ->first();

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

            $helper = app(StudentReportHelperService::class);
            $svc = new ReportCardCommentService;
            $positionMap = $this->computePositionMap($exam, $schoolId);

            $className = Section::find($stdLink->section_id)->name ?? 'class';
            $safeClass = str_replace(' ', '_', $className);
            $dir = storage_path('app/reports');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            if ($generation->mode === 'merged') {
                $merger = new \iio\libmergepdf\Merger;
                foreach ($studentIds as $sid) {
                    $myPos = $positionMap[$sid] ?? 0;
                    $pdf = \App\Http\Controllers\Admin\ReportCardsController::generatePdf(
                        $sid, $exam, $stdLink, $schoolId, $helper, $svc, $studentIds->count(), $myPos
                    );
                    $merger->addRaw($pdf);
                }
                $content = $merger->merge();
                $fileName = "report_cards_{$safeClass}_merged.pdf";
            } else {
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
                    $name = str_replace([' ', '/'], '_', $learner->name ?? $sid);
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

    private function computePositionMap(Exam $exam, int $schoolId): array
    {
        $studentIds = \App\Models\Academics\Marks::where('exam_id', $exam->id)
            ->distinct('student_id')->orderBy('student_id')->pluck('student_id');

        $helper = app(StudentReportHelperService::class);
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
}
