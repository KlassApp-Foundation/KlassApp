<?php

namespace App\Services;

use App\Jobs\GenerateClassReportsJob;
use App\Models\AcademicTerm;
use App\Models\ReportGeneration;
use App\Models\StandardLink;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Whole-class report card PDF.
 *
 * Small classes (StudentReportCardService::CLASS_PDF_DIRECT_MAX students or
 * fewer) download in the request. Larger classes are queued. The finished
 * file is stored on the generation row and downloaded from there.
 */
class ClassReportCardDownload
{
    public function __construct(
        private StudentReportCardService $reports,
        private ClassReportCardAuthorization $authorization,
    ) {
    }

    public function download(User $actor, StandardLink $stdLink, string $pendingRedirect): SymfonyResponse
    {
        $this->authorization->authorize($actor, $stdLink);

        $schoolId = (int) $actor->school_id;
        $termId = $this->requestedTermId($schoolId);
        $exam = $this->reports->resolveExam($schoolId, $stdLink, $termId);
        if (! $exam) {
            return back()->with('failmessage', 'No EOT exam found for this class.');
        }

        $learners = $this->reports->reportLearners($schoolId, $stdLink, $termId);
        if ($learners->isEmpty()) {
            return back()->with('failmessage', 'No students with marks.');
        }

        if ($learners->count() <= StudentReportCardService::CLASS_PDF_DIRECT_MAX) {
            $pdf = $this->reports->mergedPdfForLearners($schoolId, $stdLink, $learners, $termId);
            $filename = $this->reports->classPdfFilename($stdLink);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        $stdLink->loadMissing('section');
        $className = $stdLink->section->name ?? 'class';

        $generation = ReportGeneration::create([
            'school_id' => $schoolId,
            'standard_link_id' => $stdLink->id,
            'academic_term_id' => $exam->academic_term_id,
            'class_name' => $className,
            'mode' => 'merged',
            'status' => 'pending',
            'requested_by' => $actor->id,
        ]);

        GenerateClassReportsJob::dispatch($generation->id);

        return redirect($pendingRedirect)->with(
            'successmessage',
            "Preparing report cards for {$className} ({$learners->count()} students). This page shows the status, and the file can be downloaded when it is ready."
        );
    }

    public function fileResponse(User $actor, ReportGeneration $generation): SymfonyResponse
    {
        if ((int) $generation->school_id !== (int) $actor->school_id) {
            abort(404);
        }

        $stdLink = StandardLink::query()
            ->where('school_id', $actor->school_id)
            ->find($generation->standard_link_id);
        if (! $stdLink) {
            abort(404);
        }

        $this->authorization->authorize($actor, $stdLink);

        if ($generation->status !== 'completed' || ! $generation->file_path) {
            return redirect()->route('teacher.reports.cards.show', $stdLink)
                ->with('failmessage', 'This report is not ready yet.');
        }

        $absolute = storage_path('app/'.$generation->file_path);
        if (! is_file($absolute)) {
            return redirect()->route('teacher.reports.cards.show', $stdLink)
                ->with('failmessage', 'Report file no longer exists.');
        }

        return response()->download($absolute, $generation->file_name ?: $this->reports->classPdfFilename($stdLink));
    }

    private function requestedTermId(int $schoolId): ?int
    {
        $raw = request('term');
        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;
        if ($id <= 0) {
            return null;
        }

        $belongs = AcademicTerm::query()->where('school_id', $schoolId)->whereKey($id)->exists();

        return $belongs ? $id : null;
    }
}
