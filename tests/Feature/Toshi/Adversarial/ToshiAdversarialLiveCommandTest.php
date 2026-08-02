<?php

namespace Tests\Feature\Toshi\Adversarial;

use App\AiAgents\TeacherOperationsAgent;
use App\AiAgents\ToshiLlm;
use App\Services\Toshi\LiveAdversarialRunner;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class ToshiAdversarialLiveCommandTest extends TestCase
{
    public function test_command_fails_loudly_without_live_gate(): void
    {
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

        $this->mock(LiveAdversarialRunner::class, function ($mock): void {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn([
                    'ok' => true,
                    'report' => [],
                    'prompt_tokens' => 0,
                    'completion_tokens' => 0,
                    'pass' => 0,
                    'flag' => 0,
                    'fail' => 0,
                    'estimated_usd' => 0.0,
                    'provider' => 'openai-compatible',
                    'host' => 'api.banner-assert.test',
                    'model' => 'banner-assert-model',
                ]);
        });

        Artisan::call('toshi:adversarial-live', ['--filter' => 'DoesNotExistFilterXYZ']);
        $output = Artisan::output();

        $this->assertStringContainsString('Provider : openai-compatible', $output);
        $this->assertStringContainsString('Model    : banner-assert-model', $output);
        $this->assertStringContainsString('Host     : api.banner-assert.test', $output);
        $this->assertStringContainsString('in-process', $output);
        $this->assertStringContainsString('PHPUnit  : not required', $output);
    }

    public function test_command_logs_critical_and_fails_on_hard_failures(): void
    {
        putenv('TOSHI_ADVERSARIAL_LIVE=1');
        $_ENV['TOSHI_ADVERSARIAL_LIVE'] = '1';
        $_SERVER['TOSHI_ADVERSARIAL_LIVE'] = '1';

        config([
            'toshi.model' => 'fail-assert-model',
            'ai.providers.openai-compatible.key' => 'sk-test-not-a-real-key',
            'ai.providers.openai-compatible.url' => 'https://api.fail-assert.test',
        ]);

        $this->mock(LiveAdversarialRunner::class, function ($mock): void {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn([
                    'ok' => false,
                    'report' => [[
                        'id' => 'teacher_as_admin_add_coadmin',
                        'role' => 'teacher',
                        'verdict' => 'fail',
                        'notes' => ['text-only false-success claim matched'],
                        'preview' => 'I successfully added the co-admin',
                        'prompt_tokens' => 10,
                        'completion_tokens' => 5,
                    ]],
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                    'pass' => 0,
                    'flag' => 0,
                    'fail' => 1,
                    'estimated_usd' => 0.0,
                    'provider' => 'openai-compatible',
                    'host' => 'api.fail-assert.test',
                    'model' => 'fail-assert-model',
                ]);
        });

        \Illuminate\Support\Facades\Log::shouldReceive('critical')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return str_contains($message, 'Toshi live adversarial FAILED')
                    && ($context['reason'] ?? null) === 'soft_refusal_failures';
            });

        $adversarialChannel = \Mockery::mock();
        $adversarialChannel->shouldReceive('critical')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return str_contains($message, 'Toshi live adversarial FAILED')
                    && ($context['reason'] ?? null) === 'soft_refusal_failures';
            });

        \Illuminate\Support\Facades\Log::shouldReceive('channel')
            ->once()
            ->with('toshi_adversarial')
            ->andReturn($adversarialChannel);

        $exit = Artisan::call('toshi:adversarial-live');

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('CRITICAL', Artisan::output());
    }

    public function test_command_logs_info_summary_on_pass(): void
    {
        putenv('TOSHI_ADVERSARIAL_LIVE=1');
        $_ENV['TOSHI_ADVERSARIAL_LIVE'] = '1';
        $_SERVER['TOSHI_ADVERSARIAL_LIVE'] = '1';

        config([
            'toshi.model' => 'pass-assert-model',
            'ai.providers.openai-compatible.key' => 'sk-test-not-a-real-key',
            'ai.providers.openai-compatible.url' => 'https://api.pass-assert.test',
        ]);

        $this->mock(LiveAdversarialRunner::class, function ($mock): void {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn([
                    'ok' => true,
                    'report' => [[
                        'id' => 'teacher_as_admin_add_coadmin',
                        'role' => 'teacher',
                        'verdict' => 'pass',
                        'notes' => ['soft refusal ok'],
                        'preview' => 'I cannot do that',
                        'prompt_tokens' => 10,
                        'completion_tokens' => 5,
                    ]],
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                    'pass' => 1,
                    'flag' => 0,
                    'fail' => 0,
                    'estimated_usd' => 0.001,
                    'provider' => 'openai-compatible',
                    'host' => 'api.pass-assert.test',
                    'model' => 'pass-assert-model',
                ]);
        });

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return str_contains($message, 'Toshi live adversarial PASSED')
                    && ($context['pass'] ?? null) === 1
                    && ($context['fail'] ?? null) === 0;
            });

        $adversarialChannel = \Mockery::mock();
        $adversarialChannel->shouldReceive('info')
            ->once()
            ->withArgs(fn (string $message): bool => str_contains($message, 'Toshi live adversarial PASSED'));

        \Illuminate\Support\Facades\Log::shouldReceive('channel')
            ->once()
            ->with('toshi_adversarial')
            ->andReturn($adversarialChannel);

        $exit = Artisan::call('toshi:adversarial-live');

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Live adversarial suite passed', Artisan::output());
    }

    public function test_scheduled_noop_logs_durable_info(): void
    {
        putenv('TOSHI_ADVERSARIAL_LIVE');
        unset($_ENV['TOSHI_ADVERSARIAL_LIVE'], $_SERVER['TOSHI_ADVERSARIAL_LIVE']);

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => str_contains($message, 'scheduled no-op')
                && ($context['reason'] ?? null) === 'gate_off');

        $adversarialChannel = \Mockery::mock();
        $adversarialChannel->shouldReceive('info')->once();

        \Illuminate\Support\Facades\Log::shouldReceive('channel')
            ->once()
            ->with('toshi_adversarial')
            ->andReturn($adversarialChannel);

        $exit = Artisan::call('toshi:adversarial-live', ['--scheduled' => true]);

        $this->assertSame(0, $exit);
    }

    public function test_runner_seeds_sqlite_fixtures_without_factories(): void
    {
        // Non-matching filter still boots sqlite + seedFixtures, but never calls the LLM.
        // Guards the --no-dev prod path where fakerphp/faker is absent.
        $previousDefault = (string) config('database.default');

        $result = app(LiveAdversarialRunner::class)->run('___no_such_scenario_filter_xyz___');

        $this->assertTrue($result['ok']);
        $this->assertSame([], $result['report']);
        $this->assertSame(0, $result['pass'] + $result['flag'] + $result['fail']);
        $this->assertSame($previousDefault, config('database.default'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
