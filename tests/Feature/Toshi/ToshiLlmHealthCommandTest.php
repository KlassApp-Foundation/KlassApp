<?php

namespace Tests\Feature\Toshi;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ToshiLlmHealthCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'toshi.model' => 'deepseek-v4-flash',
            'toshi.llm_env' => [
                'openai_compatible_model' => 'deepseek-v4-flash',
                'toshi_llm_model' => null,
                'openai_compatible_url' => 'https://api.deepseek.com',
                'toshi_llm_base_url' => null,
            ],
            'ai.providers.openai-compatible.url' => 'https://api.deepseek.com',
            'ai.providers.openai-compatible.key' => 'sk-test-health-key',
        ]);
    }

    public function test_health_check_succeeds_on_valid_completion_response(): void
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'id' => 'chatcmpl-health',
                'object' => 'chat.completion',
                'choices' => [[
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'TOSHI_HEALTH_OK',
                    ],
                    'finish_reason' => 'stop',
                ]],
            ], 200),
        ]);

        $exit = Artisan::call('toshi:llm-health');
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Result   : OK', $output);
        $this->assertStringNotContainsString('sk-test-health-key', $output);
        $this->assertStringNotContainsString('https://api.deepseek.com', $output);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'chat/completions')
                && ($request['model'] ?? null) === 'deepseek-v4-flash';
        });
    }

    public function test_health_check_reports_provider_http_failure_as_critical(): void
    {
        Log::spy();

        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'error' => [
                    'message' => 'The supported API model names are deepseek-v4-pro or deepseek-v4-flash, but you passed meta/llama-3.1-8b-instruct.',
                    'type' => 'invalid_request_error',
                ],
            ], 400),
        ]);

        $exit = Artisan::call('toshi:llm-health');
        $output = Artisan::output();

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('CRITICAL', $output);
        $this->assertStringContainsString('HTTP 400', $output);

        Log::shouldHaveReceived('critical')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return str_contains($message, 'Toshi LLM health check FAILED')
                    && ($context['reason'] ?? null) === 'http_error'
                    && ($context['http_status'] ?? null) === 400;
            });
    }

    public function test_health_check_fails_on_config_conflict_without_live_call(): void
    {
        Log::spy();

        config([
            'toshi.model' => 'meta/llama-3.1-8b-instruct',
            'toshi.llm_env' => [
                'openai_compatible_model' => null,
                'toshi_llm_model' => 'meta/llama-3.1-8b-instruct',
                'openai_compatible_url' => null,
                'toshi_llm_base_url' => null,
            ],
            'ai.providers.openai-compatible.url' => 'https://api.deepseek.com',
        ]);

        Http::fake();

        $exit = Artisan::call('toshi:llm-health');
        $output = Artisan::output();

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('CRITICAL', $output);
        $this->assertStringContainsString('incompatible with URL host', $output);

        Http::assertNothingSent();

        Log::shouldHaveReceived('critical')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return str_contains($message, 'Toshi LLM health check FAILED')
                    && ($context['reason'] ?? null) === 'config_conflict';
            });
    }

    public function test_health_check_fails_on_empty_assistant_content(): void
    {
        Log::spy();

        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'id' => 'chatcmpl-empty',
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => ''],
                ]],
            ], 200),
        ]);

        $exit = Artisan::call('toshi:llm-health');

        $this->assertSame(1, $exit);
        Log::shouldHaveReceived('critical')
            ->once()
            ->withArgs(fn (string $message, array $context) => ($context['reason'] ?? null) === 'empty_response');
    }
}
