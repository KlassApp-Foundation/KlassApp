<?php

namespace App\Jobs;

use App\Models\OnboardingUpload;
use App\Services\Onboarding\OnboardingUploadService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reads one onboarding roster file and imports its rows, updating the
 * upload row as it goes so the wizard can show progress. Runs on the queue,
 * never inside the page request.
 */
class ProcessOnboardingUpload implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public int $uploadId) {}

    public function handle(OnboardingUploadService $service): void
    {
        $upload = OnboardingUpload::find($this->uploadId);
        if (! $upload || $upload->status === OnboardingUpload::STATUS_DONE) {
            return;
        }

        $upload->forceFill(['status' => OnboardingUpload::STATUS_RUNNING, 'error' => null])->save();

        try {
            $rows = $upload->rows;
            if (! is_array($rows) || $rows === []) {
                $rows = $service->extract($upload);
            }

            $upload->forceFill(['rows' => $rows, 'total' => count($rows)])->save();

            $result = $service->import(
                $upload,
                $rows,
                function (int $processed, int $added, int $skipped) use ($upload): void {
                    $upload->forceFill([
                        'processed' => $processed,
                        'added' => $added,
                        'skipped' => $skipped,
                    ])->save();
                }
            );

            $upload->forceFill([
                'status' => OnboardingUpload::STATUS_DONE,
                'processed' => count($rows),
                'added' => $result['added'],
                'skipped' => $result['skipped'],
                'report' => $result['report'],
                'skips' => $result['skips'],
                'notes' => $result['notes'],
                'missing_streams' => $result['missing_streams'],
                'finished_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $upload->forceFill([
                'status' => OnboardingUpload::STATUS_FAILED,
                'error' => $e->getMessage(),
                'finished_at' => now(),
            ])->save();

            Log::warning('Onboarding upload failed', [
                'upload_id' => (int) $upload->id,
                'school_id' => (int) $upload->school_id,
                'kind' => (string) $upload->kind,
            ]);
        }
    }
}
