<?php

namespace Tests\Feature\Toshi\Adversarial;

use App\AiAgents\TeacherOperationsAgent;
use App\AiAgents\ToshiLlm;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ToshiAdversarialLiveCommandTest extends TestCase
{
    public function test_command_fails_loudly_without_live_gate(): void
    {
        // Ensure gate is off for this process (phpunit does not set it).
        putenv('TOSHI_ADVERSARIAL_LIVE');
        unset($_ENV['TOSHI_ADVERSARIAL_LIVE'], $_SERVER['TOSHI_ADVERSARIAL_LIVE']);

        $exit = Artisan::call('toshi:adversarial-live');
        $output = Artisan::output();

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('TOSHI_ADVERSARIAL_LIVE', $output);
    }

    public function test_scheduled_mode_noops_without_live_gate(): void
    {
        putenv('TOSHI_ADVERSARIAL_LIVE');
        unset($_ENV['TOSHI_ADVERSARIAL_LIVE'], $_SERVER['TOSHI_ADVERSARIAL_LIVE']);

        $exit = Artisan::call('toshi:adversarial-live', ['--scheduled' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('scheduled no-op', $output);
    }

    public function test_toshi_llm_resolves_model_from_config(): void
    {
        config([
            'toshi.model' => 'configured-adversarial-model',
            'ai.providers.openai-compatible.models.text.default' => 'should-not-win',
            'ai.providers.openai-compatible.url' => 'https://api.example-llm.test/v1',
        ]);

        $this->assertSame('openai-compatible', ToshiLlm::provider());
        $this->assertSame('configured-adversarial-model', ToshiLlm::model());
        $this->assertSame('api.example-llm.test', ToshiLlm::urlHost());
    }

    public function test_operations_agent_uses_same_model_as_adversarial_resolver(): void
    {
        config(['toshi.model' => 'shared-live-model-slug']);

        $agent = new TeacherOperationsAgent;

        $this->assertSame(ToshiLlm::model(), $agent->model());
        $this->assertSame(ToshiLlm::provider(), $agent->provider());
    }

    public function test_command_banner_reports_configured_toshi_model(): void
    {
        putenv('TOSHI_ADVERSARIAL_LIVE=1');
        $_ENV['TOSHI_ADVERSARIAL_LIVE'] = '1';
        $_SERVER['TOSHI_ADVERSARIAL_LIVE'] = '1';

        config([
            'toshi.model' => 'banner-assert-model',
            'ai.providers.openai-compatible.key' => 'sk-test-not-a-real-key',
            'ai.providers.openai-compatible.url' => 'https://api.banner-assert.test',
        ]);

        // Subprocess may fail (fake key) — we only assert the banner printed first.
        Artisan::call('toshi:adversarial-live', ['--filter' => 'DoesNotExistFilterXYZ']);
        $output = Artisan::output();

        $this->assertStringContainsString('Provider : openai-compatible', $output);
        $this->assertStringContainsString('Model    : banner-assert-model', $output);
        $this->assertStringContainsString('Host     : api.banner-assert.test', $output);
    }
}
