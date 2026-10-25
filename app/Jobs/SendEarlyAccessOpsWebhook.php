<?php

namespace App\Jobs;

use App\Services\EarlyAccessOpsWebhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * POSTs one early-access ops webhook. The payload is loaded when the job
 * runs; this object stores only the request id, the event, and the unix
 * time of the transition. Five tries, backoff 10s, 30s, 60s, then 120s.
 * A failure is logged with the request id, the event, and the HTTP status
 * or exception class. The body, names, email, phone, secret, and URL are not.
 */
class SendEarlyAccessOpsWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 15;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60, 120];

    public function __construct(
        public int $requestId,
        public string $event,
        public int $occurredAt,
    ) {
        // Queueable already declares $afterCommit. A direct dispatch waits
        // for the open transaction. EarlyAccessOpsWebhook turns this off
        // after it has registered its own after-commit callback.
        $this->afterCommit = true;
    }

    public function handle(EarlyAccessOpsWebhook $webhook): void
    {
        $webhook->deliver($this->requestId, $this->event, $this->occurredAt);
    }

    public function failed(?Throwable $e): void
    {
        Log::warning('Early access ops webhook exhausted retries', [
            'early_access_request_id' => $this->requestId,
            'event' => $this->event,
            'exception' => $e ? $e::class : null,
        ]);
    }
}
