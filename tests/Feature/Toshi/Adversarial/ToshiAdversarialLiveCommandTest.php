<?php

namespace Tests\Feature\Toshi\Adversarial;

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
}
