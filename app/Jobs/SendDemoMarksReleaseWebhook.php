<?php

namespace App\Jobs;

use App\Services\MarksRelease\MarksReleaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Demo-only: tell a presenter's n8n workflow that marks were released.
 *
 * Queued so a slow or failing webhook never delays or fails the release
 * itself. The service re-checks every gate (demo school, enabled flag,
 * environment, URL, secret) before it posts.
 */
class SendDemoMarksReleaseWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 10;

    public function __construct(
        public int $releaseId,
        public int $learners,
        public int $classes,
    ) {
    }

    public function handle(MarksReleaseService $releases): void
    {
        try {
            $releases->sendDemoWebhook($this->releaseId, $this->learners, $this->classes);
        } catch (\Throwable $e) {
            Log::warning('Demo release webhook job failed', [
                'release_id' => $this->releaseId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
