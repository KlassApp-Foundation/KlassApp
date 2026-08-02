<?php

namespace App\Console\Commands;

use App\AiAgents\ToshiLlm;
use App\Exceptions\AmbiguousToshiLlmConfigException;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Live completion health check for the configured Toshi LLM provider.
 *
 * Distinct from toshi:llm-status (config dump only): this command performs one
 * minimal chat/completions request and verifies a real assistant response.
 *
 * Ops (VPS): docker exec sms-app php artisan toshi:llm-health
 * Exit code: 0 success, 1 critical failure — suitable for cron/k8s monitors.
 * Schedule is NOT wired in Kernel; OpenCode/ops owns production cadence.
 *
 * Alerting: no Sentry/Bugsnag in this app. On failure we Log::critical and
 * return FAILURE so an external monitor watching exit codes can page.
 * Never prints API keys or full URLs that may embed credentials.
 */
class ToshiLlmHealthCommand extends Command
{
    protected $signature = 'toshi:llm-health';

    protected $description = 'Live Toshi LLM health check: one cheap completion against the configured provider';

    public function handle(): int
    {
        $this->info('Toshi LLM health check');

        try {
            ToshiLlm::assertConfigConsistent();
        } catch (AmbiguousToshiLlmConfigException $e) {
            return $this->failCritical('config_conflict', $e->getMessage(), $e->context());
        }

        if (! ToshiLlm::keyConfigured()) {
            return $this->failCritical('missing_api_key', 'openai-compatible API key is not configured.', [
                'provider' => ToshiLlm::provider(),
                'model' => ToshiLlm::model(),
                'url_host' => ToshiLlm::urlHost(),
            ]);
        }

        $model = ToshiLlm::model();
        $host = ToshiLlm::urlHost();
        $this->line('  Provider : '.ToshiLlm::provider());
        $this->line('  Model    : '.$model);
        $this->line('  URL host : '.($host !== '' ? $host : '(unresolved)'));
        $this->line('  Checksum : '.ToshiLlm::configChecksum());

        $baseUrl = rtrim(ToshiLlm::url(), '/');
        if ($baseUrl === '') {
            return $this->failCritical('missing_url', 'openai-compatible URL is empty.', [
                'provider' => ToshiLlm::provider(),
                'model' => $model,
            ]);
        }

        $key = (string) config('ai.providers.openai-compatible.key', '');

        try {
            $response = Http::baseUrl($baseUrl)
                ->withToken($key)
                ->timeout(15)
                ->connectTimeout(5)
                ->acceptJson()
                ->post('chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Reply with exactly: TOSHI_HEALTH_OK'],
                    ],
                    'max_tokens' => 128,
                    'temperature' => 0,
                ]);
        } catch (ConnectionException $e) {
            return $this->failCritical('connection_error', 'LLM provider connection failed: '.$e->getMessage(), [
                'provider' => ToshiLlm::provider(),
                'model' => $model,
                'url_host' => $host,
            ]);
        } catch (Throwable $e) {
            return $this->failCritical('request_error', 'LLM health request failed: '.$e->getMessage(), [
                'provider' => ToshiLlm::provider(),
                'model' => $model,
                'url_host' => $host,
                'exception' => $e::class,
            ]);
        }

        if ($response->failed()) {
            return $this->failCritical('http_error', 'LLM provider returned HTTP '.$response->status(), [
                'provider' => ToshiLlm::provider(),
                'model' => $model,
                'url_host' => $host,
                'http_status' => $response->status(),
                'body_preview' => mb_substr((string) $response->body(), 0, 240),
            ]);
        }

        $json = $response->json();

        // Reasoning models (e.g. deepseek-v4-flash) spend the first completion
        // tokens on reasoning_content. A health check must succeed when the
        // provider is alive and returned either an assistant reply or a
        // non-empty reasoning trace — otherwise it false-fails on healthy
        // providers (seen on prod: max_tokens=16 + temperature=0 exhausted on
        // reasoning, empty content, exit 1 despite a 200).
        $content = data_get($json, 'choices.0.message.content');
        $reasoning = (string) data_get($json, 'choices.0.message.reasoning_content', '');
        if ((! is_string($content) || trim($content) === '') && trim($reasoning) === '') {
            return $this->failCritical('empty_response', 'LLM provider returned no assistant content.', [
                'provider' => ToshiLlm::provider(),
                'model' => $model,
                'url_host' => $host,
                'http_status' => $response->status(),
            ]);
        }

        $snippet = trim(is_string($content) && $content !== '' ? $content : $reasoning);
        $this->info('  Result   : OK (received '.mb_strlen($snippet).' chars)');
        $this->comment('Live completion succeeded. No secrets printed. Schedule not wired here.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function failCritical(string $reason, string $message, array $context = []): int
    {
        $payload = array_merge(['reason' => $reason], $context);

        // configChecksum() calls model()/provider(); skip when config is already broken.
        try {
            $payload['checksum'] = ToshiLlm::configChecksum();
        } catch (Throwable) {
            // Intentionally empty — conflict already reported in $context.
        }

        Log::critical('Toshi LLM health check FAILED: '.$message, $payload);

        $this->error('CRITICAL: '.$message);
        $this->line('  Reason: '.$reason);
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $this->line('  '.$key.': '.(string) $value);
            }
        }

        return self::FAILURE;
    }
}
