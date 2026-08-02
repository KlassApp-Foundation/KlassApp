<?php

namespace Tests\Feature\Toshi;

use App\AiAgents\ToshiLlm;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ToshiLlmStatusCommandTest extends TestCase
{
    public function test_reports_provider_model_and_host_without_secrets(): void
    {
        $fakeKey = 'sk-live-FAKESECRET_do_not_print_this_value_xyz789';

        config([
            'toshi.model' => 'deepseek-chat',
            'ai.providers.openai-compatible.url' => 'https://api.deepseek.com/v1',
            'ai.providers.openai-compatible.key' => $fakeKey,
        ]);

        $exit = Artisan::call('toshi:llm-status');
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Provider       : openai-compatible', $output);
        $this->assertStringContainsString('Model          : deepseek-chat', $output);
        $this->assertStringContainsString('URL host       : api.deepseek.com', $output);
        $this->assertStringContainsString('API key        : configured', $output);
        $this->assertStringContainsString('Config checksum: '.ToshiLlm::configChecksum(), $output);

        $this->assertStringNotContainsString($fakeKey, $output);
        $this->assertStringNotContainsString('sk-live-', $output);
        $this->assertStringNotContainsString('https://api.deepseek.com', $output);
        $this->assertStringNotContainsString('/v1', $output);
    }

    public function test_reports_missing_key_without_printing_empty_secret_noise(): void
    {
        config([
            'toshi.model' => 'configured-status-model',
            'ai.providers.openai-compatible.url' => 'https://llm.example.test',
            'ai.providers.openai-compatible.key' => '',
        ]);

        $exit = Artisan::call('toshi:llm-status');
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Model          : configured-status-model', $output);
        $this->assertStringContainsString('URL host       : llm.example.test', $output);
        $this->assertStringContainsString('API key        : missing', $output);
        $this->assertStringNotContainsString('Bearer', $output);
    }
}
