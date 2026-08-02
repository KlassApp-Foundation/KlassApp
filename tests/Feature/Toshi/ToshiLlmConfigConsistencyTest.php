<?php

namespace Tests\Feature\Toshi;

use App\AiAgents\ToshiLlm;
use App\Exceptions\AmbiguousToshiLlmConfigException;
use Tests\TestCase;

class ToshiLlmConfigConsistencyTest extends TestCase
{
    /**
     * Exact dual-config shape from the 2026-08-02 production incident:
     * NVIDIA-ish TOSHI_LLM_MODEL with DeepSeek openai-compatible URL and
     * OPENAI_COMPATIBLE_MODEL unset → silent wrong model → empty conversations.
     */
    public function test_incident_shape_nvidia_model_against_deepseek_url_fails_loudly(): void
    {
        config([
            'toshi.model' => 'meta/llama-3.1-8b-instruct',
            'toshi.llm_env' => [
                'openai_compatible_model' => null,
                'toshi_llm_model' => 'meta/llama-3.1-8b-instruct',
                'openai_compatible_url' => 'https://api.deepseek.com',
                'toshi_llm_base_url' => null,
            ],
            'ai.providers.openai-compatible.url' => 'https://api.deepseek.com',
            'ai.providers.openai-compatible.key' => 'sk-test',
        ]);

        $this->expectException(AmbiguousToshiLlmConfigException::class);
        $this->expectExceptionMessage('incompatible with URL host');

        ToshiLlm::assertConfigConsistent();
    }

    public function test_model_resolver_throws_on_incident_shape(): void
    {
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

        $this->expectException(AmbiguousToshiLlmConfigException::class);

        ToshiLlm::model();
    }

    public function test_both_models_set_and_differing_fails_loudly(): void
    {
        config([
            'toshi.model' => 'deepseek-v4-flash',
            'toshi.llm_env' => [
                'openai_compatible_model' => 'deepseek-v4-flash',
                'toshi_llm_model' => 'meta/llama-3.1-8b-instruct',
                'openai_compatible_url' => 'https://api.deepseek.com',
                'toshi_llm_base_url' => null,
            ],
            'ai.providers.openai-compatible.url' => 'https://api.deepseek.com',
        ]);

        $this->expectException(AmbiguousToshiLlmConfigException::class);
        $this->expectExceptionMessage('OPENAI_COMPATIBLE_MODEL and TOSHI_LLM_MODEL are both set and differ');

        ToshiLlm::assertConfigConsistent();
    }

    public function test_differing_url_hosts_fail_loudly(): void
    {
        config([
            'toshi.model' => 'deepseek-v4-flash',
            'toshi.llm_env' => [
                'openai_compatible_model' => 'deepseek-v4-flash',
                'toshi_llm_model' => null,
                'openai_compatible_url' => 'https://api.deepseek.com',
                'toshi_llm_base_url' => 'https://integrate.api.nvidia.com/v1',
            ],
            'ai.providers.openai-compatible.url' => 'https://api.deepseek.com',
        ]);

        $this->expectException(AmbiguousToshiLlmConfigException::class);
        $this->expectExceptionMessage('different hosts');

        ToshiLlm::assertConfigConsistent();
    }

    public function test_aligned_deepseek_config_passes(): void
    {
        config([
            'toshi.model' => 'deepseek-v4-flash',
            'toshi.llm_env' => [
                'openai_compatible_model' => 'deepseek-v4-flash',
                'toshi_llm_model' => null,
                'openai_compatible_url' => 'https://api.deepseek.com',
                'toshi_llm_base_url' => null,
            ],
            'ai.providers.openai-compatible.url' => 'https://api.deepseek.com',
        ]);

        ToshiLlm::assertConfigConsistent();

        $this->assertSame('deepseek-v4-flash', ToshiLlm::model());
        $this->assertSame('openai-compatible', ToshiLlm::provider());
    }

    public function test_identical_dual_model_values_are_allowed(): void
    {
        config([
            'toshi.model' => 'deepseek-v4-flash',
            'toshi.llm_env' => [
                'openai_compatible_model' => 'deepseek-v4-flash',
                'toshi_llm_model' => 'deepseek-v4-flash',
                'openai_compatible_url' => 'https://api.deepseek.com',
                'toshi_llm_base_url' => null,
            ],
            'ai.providers.openai-compatible.url' => 'https://api.deepseek.com',
        ]);

        ToshiLlm::assertConfigConsistent();

        $this->assertSame('deepseek-v4-flash', ToshiLlm::model());
    }
}
