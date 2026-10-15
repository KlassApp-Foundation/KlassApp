<?php

namespace App\Jobs;

use App\Services\MarksRelease\MarksReleaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Sends ONE parent's marks-release message (Task 9 Tier 2, "Release now" or a
 * scheduled release). The payload holds ids only: the number and the text are
 * read when the job runs, so a number that stopped following in the meantime
 * gets nothing. The demo-only send guard applies exactly as before.
 *
 * Idempotent: the release's result entry for this (number, student) is
 * checked under a row lock, so a retry never sends twice to a parent already
 * marked sent. A Meta/network failure is retried (3 tries, 30 s then 2 min);
 * after the last try the entry shows "Failed" on the release page and the job
 * lands in failed_jobs.
 */
class SendMarksReleaseMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(
        public int $releaseId,
        public int $bindingId,
        public int $studentId,
        public int $standardLinkId,
        public string $key,
    ) {}

    public function handle(MarksReleaseService $releases): void
    {
        $releases->deliverOne($this->releaseId, $this->bindingId, $this->studentId, $this->standardLinkId, $this->key, $this->attempts(), $this->tries);
    }

    public function failed(?Throwable $e): void
    {
        app(MarksReleaseService::class)->markFailed($this->releaseId, $this->key, $e?->getMessage() ?? 'unknown error');
    }
}
