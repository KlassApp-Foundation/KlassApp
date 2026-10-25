<?php

namespace App\Console\Commands;

use App\AiAgents\ToshiLlm;
use App\AiAgents\ToshiLlmProbe;
use App\Exceptions\AmbiguousToshiLlmConfigException;
use App\Exceptions\MissingToshiLlmApiKeyException;
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
    protected $signature = 'toshi:llm-health {--live : When the provider is Bedrock, send one Converse probe. Without this flag Bedrock is config-only.}';

    protected $description = 'Live Toshi LLM health check: one cheap completion against the configured provider';

    public function handle(): int
    {
        $this->info('Toshi LLM health check');

        try {
            ToshiLlm::assertConfigConsistent();
        } catch (AmbiguousToshiLlmConfigException $e) {
            return $this->failCritical('config_conflict', $e->getMessage(), $e->context());
        }

        if (ToshiLlm::configuredProvider() === 'bedrock') {
            return $this->bedrockHealth();
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
     * Bedrock does not speak the openai-compatible chat/completions probe.
     * Default is config-only so a missing AWS chain cannot hang on instance metadata.
     * `--live` sends one Converse request through ToshiLlmProbe. Not verified
     * against a real Bedrock account in this repository.
     */
    private function bedrockHealth(): int
    {
        try {
            $provider = ToshiLlm::provider();
            $model = ToshiLlm::model();
        } catch (AmbiguousToshiLlmConfigException $e) {
            return $this->failCritical('config_conflict', $e->getMessage(), $e->context());
        } catch (MissingToshiLlmApiKeyException $e) {
            return $this->failCritical('missing_credentials', $e->getMessage(), $e->context());
        }

        $region = (string) config('ai.providers.bedrock.region', '');
        $this->line('  Provider : '.$provider);
        $this->line('  Model    : '.$model);
        $this->line('  Region   : '.$region);
        $this->line('  Auth     : '.ToshiLlm::credentialSummary());
        $this->line('  Checksum : '.ToshiLlm::configChecksum());

        if (! $this->option('live')) {
            $this->info('  Result   : config OK (live Bedrock call skipped; re-run with --live)');
            $this->comment('No secrets printed. A live Converse call was not attempted.');

            return self::SUCCESS;
        }

        try {
            $text = trim((string) (new ToshiLlmProbe)->prompt('Reply with exactly: TOSHI_HEALTH_OK')->text);
        } catch (Throwable $e) {
            return $this->failCritical('bedrock_error', 'Bedrock probe failed: '.$e::class, [
                'provider' => 'bedrock',
                'model' => $model,
                'region' => $region,
                'exception' => $e::class,
                'message' => $this->safeError($e->getMessage()),
            ]);
        }

        if ($text === '') {
            return $this->failCritical('empty_response', 'Bedrock returned no assistant content.', [
                'provider' => 'bedrock',
                'model' => $model,
                'region' => $region,
            ]);
        }

        $this->info('  Result   : OK (received '.mb_strlen($text).' chars)');
        $this->comment('Live Bedrock completion succeeded. No secrets printed.');

        return self::SUCCESS;
    }

    private function safeError(string $message): string
    {
        $message = preg_replace('/sk-[A-Za-z0-9_\-]{6,}/', '[redacted]', $message) ?? $message;
        $message = preg_replace('/AKIA[A-Z0-9]{8,}/', '[redacted]', $message) ?? $message;
        $message = preg_replace('/(?i)(secret|token|password|api[_-]?key)\s*[=:]\s*\S+/', '$1=[redacted]', $message) ?? $message;

        return mb_substr($message, 0, 240);
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
