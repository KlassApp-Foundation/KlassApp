<?php

namespace App\Services;

use App\Jobs\SendEarlyAccessOpsWebhook;
use App\Models\EarlyAccessRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Queues a signed POST to the ops webhook when an early-access request
 * is created, approved, invited, or claimed.
 *
 * Disabled when KLASSAPP_OPS_WEBHOOK_URL is empty: nothing is queued.
 * A URL without KLASSAPP_OPS_WEBHOOK_SECRET is not sent unsigned.
 *
 * The signature is HMAC-SHA256 of the exact raw JSON body, hex-encoded:
 *   X-KlassApp-Signature: sha256=<hex>
 *   X-KlassApp-Timestamp: unix seconds for the same instant as "timestamp"
 *
 * n8n: respond immediately. Verify HMAC-SHA256 of the raw body (do not
 * re-encode the JSON) against X-KlassApp-Signature. Require
 * X-KlassApp-Timestamp to match the body's timestamp, and accept events
 * up to about 10 minutes old. Drop duplicates on id + event + timestamp.
 * Put no token in the URL. Retries send the same body.
 */
class EarlyAccessOpsWebhook
{
    public const EVENTS = ['created', 'approved', 'invited', 'claimed'];

    public function queue(EarlyAccessRequest $request, string $event): void
    {
        $requestId = (int) $request->id;

        if (! $this->shouldQueue($requestId, $event)) {
            return;
        }

        $occurredAt = now()->utc()->getTimestamp();

        $push = function () use ($requestId, $event, $occurredAt): void {
            try {
                $job = new SendEarlyAccessOpsWebhook($requestId, $event, $occurredAt);
                // This callback already runs after the open transaction commits.
                // Leave the job's own afterCommit flag off here so the push is
                // not registered a second time and then dropped.
                $job->afterCommit = false;
                Bus::dispatch($job);
            } catch (Throwable $e) {
                $this->logDispatchFailure($requestId, $event, $e);
            }
        };

        try {
            DB::afterCommit($push);
        } catch (Throwable $e) {
            $this->logDispatchFailure($requestId, $event, $e);
        }
    }

    public function deliver(int $requestId, string $event, int $occurredAt): void
    {
        $url = $this->url();
        if ($url === null) {
            return;
        }

        $secret = $this->secret();
        if ($secret === null) {
            $this->logWarning('Early access ops webhook skipped: secret is not set', $requestId, $event);

            return;
        }

        if (! in_array($event, self::EVENTS, true)) {
            return;
        }

        $request = EarlyAccessRequest::query()->find($requestId);
        if ($request === null) {
            $this->logWarning('Early access ops webhook skipped: request is gone', $requestId, $event);

            return;
        }

        $body = $this->body($request, $event, $occurredAt);

        try {
            $response = Http::timeout(5)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-KlassApp-Signature' => 'sha256='.hash_hmac('sha256', $body, $secret),
                    'X-KlassApp-Timestamp' => (string) $occurredAt,
                ])
                ->withBody($body, 'application/json')
                ->post($url);
        } catch (Throwable $e) {
            $this->logWarning('Early access ops webhook failed', $requestId, $event, [
                'exception' => $e::class,
            ]);

            throw new RuntimeException('Early access ops webhook request failed');
        }

        if (! $response->successful()) {
            $this->logWarning('Early access ops webhook failed', $requestId, $event, [
                'http_status' => $response->status(),
            ]);

            throw new RuntimeException('Early access ops webhook failed with HTTP '.$response->status());
        }
    }

    private function shouldQueue(int $requestId, string $event): bool
    {
        if (! in_array($event, self::EVENTS, true)) {
            return false;
        }

        $url = $this->url();
        if ($url === null) {
            $raw = trim((string) config('services.ops_webhook.url'));
            if ($raw !== '') {
                $this->logWarning('Early access ops webhook skipped: URL is not https', $requestId, $event);
            }

            return false;
        }

        if ($this->secret() === null) {
            $this->logWarning('Early access ops webhook skipped: secret is not set', $requestId, $event);

            return false;
        }

        return true;
    }

    private function url(): ?string
    {
        $url = trim((string) config('services.ops_webhook.url'));
        if ($url === '' || ! str_starts_with($url, 'https://') || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $url;
    }

    private function secret(): ?string
    {
        $secret = trim((string) config('services.ops_webhook.secret'));

        return $secret === '' ? null : $secret;
    }

    private function body(EarlyAccessRequest $request, string $event, int $occurredAt): string
    {
        // early_access_requests has no status column. status is the transition.
        $payload = [
            'event' => $event,
            'id' => (int) $request->id,
            'school_name' => (string) $request->school_name,
            'contact_name' => (string) $request->contact_name,
            'email' => (string) $request->email,
            'phone' => (string) $request->phone,
            'country' => (string) $request->country,
            'approximate_students' => (int) $request->approx_students,
            'status' => $event,
            'timestamp' => Carbon::createFromTimestamp($occurredAt, 'UTC')->format('Y-m-d\TH:i:s\Z'),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Early access ops webhook payload could not be encoded');
        }

        return $json;
    }

    /**
     * @param  array<string, int|string|null>  $context
     */
    private function logWarning(string $message, int $requestId, string $event, array $context = []): void
    {
        Log::warning($message, [
            'early_access_request_id' => $requestId,
            'event' => $event,
        ] + $context);
    }

    private function logDispatchFailure(int $requestId, string $event, Throwable $e): void
    {
        $this->logWarning('Early access ops webhook dispatch failed', $requestId, $event, [
            'exception' => $e::class,
        ]);
    }
}
