<?php

namespace App\Jobs;

use App\Models\OnboardingUpload;
use App\Services\Onboarding\OnboardingUploadService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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

        // The importer calls the same component a signed-in admin would use,
        // so it needs that identity — but only for as long as this job runs.
        // A queue worker is long-lived; leaving the user on the guard would
        // make every later job run as this school admin.
        $previousUser = Auth::user();
        $owner = User::query()->find($upload->user_id);
        if (! $owner) {
            $upload->forceFill([
                'status' => OnboardingUpload::STATUS_FAILED,
                'error' => 'The person who uploaded this file no longer exists.',
                'finished_at' => now(),
            ])->save();

            return;
        }
        Auth::setUser($owner);

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
        } finally {
            Auth::forgetGuards();
            if ($previousUser) {
                Auth::setUser($previousUser);
            }
        }
    }
}
