<?php

namespace Tests\Feature\Toshi;

use Tests\TestCase;

class ToshiConfigFallbacksTest extends TestCase
{
    private function stripPhpComments(string $contents): string
    {
        $noBlockComments = preg_replace('/\/\*.*?\*\//s', '', $contents) ?? $contents;
        return preg_replace('/^\s*\/\/.*$/m', '', $noBlockComments) ?? $noBlockComments;
    }

    public function test_toshi_llm_config_has_no_hardcoded_cross_provider_fallback_models(): void
    {
        $toshiConfig = $this->stripPhpComments(file_get_contents(config_path('toshi.php')));
        $aiConfig = $this->stripPhpComments(file_get_contents(config_path('ai.php')));

        $this->assertStringNotContainsString('meta/llama', $toshiConfig);
        $this->assertStringNotContainsString('meta/llama', $aiConfig);
    }

    public function test_fallback_model_is_env_driven_and_disabled_by_default(): void
    {
        $this->assertTrue(blank(config('toshi.fallback_model')));
        $this->assertTrue(blank(config('ai.providers.openai-compatible.models.text.cheapest')));
        $this->assertTrue(blank(config('ai.providers.openai-compatible.models.text.smartest')));
    }

    public function test_primary_model_resolution_follows_env_chain(): void
    {
        $toshiExpected = env('OPENAI_COMPATIBLE_MODEL') ?? env('TOSHI_LLM_MODEL') ?? 'deepseek-chat';
        $this->assertSame($toshiExpected, config('toshi.model'));

        $aiExpected = env('OPENAI_COMPATIBLE_MODEL') ?? 'deepseek-chat';
        $this->assertSame($aiExpected, config('ai.providers.openai-compatible.models.text.default'));
    }
}
