<?php

namespace App\Console\Commands;

use App\AiAgents\ToshiLlm;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Manual / gated runner for live-LLM adversarial soft-refusal checks.
 *
 * Requires TOSHI_ADVERSARIAL_LIVE=1 and a real openai-compatible API key.
 * Delegates to PHPUnit @group live-llm (excluded from default CI by skip gate).
 * Does not send WhatsApp to real parents — live tests Http::fake outbound HTTP
 * and use phpunit.xml sqlite :memory: (not production DB).
 */
class ToshiAdversarialLiveCommand extends Command
{
    protected $signature = 'toshi:adversarial-live
                            {--filter= : Optional PHPUnit --filter for a subset of live scenarios}
                            {--scheduled : Quiet no-op when gate/keys missing (for Kernel schedule)}';

    protected $description = 'Run live-LLM Toshi adversarial soft-refusal suite (@group live-llm)';

    public function handle(): int
    {
        $scheduled = (bool) $this->option('scheduled');
        $live = $this->liveGateEnabled();
        $key = (string) config('ai.providers.openai-compatible.key');

        if (! $live) {
            $message = 'TOSHI_ADVERSARIAL_LIVE is not set to 1 — refusing to call a live model.';
            if ($scheduled) {
                $this->comment($message.' (scheduled no-op)');

                return self::SUCCESS;
            }
            $this->error($message);

            return self::FAILURE;
        }

        if ($key === '') {
            $message = 'ai.providers.openai-compatible.key is empty — cannot run live adversarial suite.';
            if ($scheduled) {
                $this->comment($message.' (scheduled no-op)');

                return self::SUCCESS;
            }
            $this->error($message);

            return self::FAILURE;
        }

        $this->info('Toshi live adversarial runner');
        $this->line('  Provider : '.ToshiLlm::provider());
        $this->line('  URL      : '.ToshiLlm::url());
        $this->line('  Host     : '.ToshiLlm::urlHost());
        $this->line('  Model    : '.ToshiLlm::model());
        $this->line('  Key      : set ('.strlen($key).' chars)');
        $this->line('  App DB   : '.config('database.default').' / '.(string) config('database.connections.'.config('database.default').'.database').' (artisan boot only)');
        $this->line('  Test DB  : phpunit.xml sqlite :memory: (scenarios do not use production)');
        $this->line('  WhatsApp : Http::fake in live tests — no real parent messages');
        $this->newLine();

        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $args = [$php, $artisan, 'test', '--compact', '--group=live-llm'];
        $filter = $this->option('filter');
        if (is_string($filter) && $filter !== '') {
            $args[] = '--filter='.$filter;
        }

        $process = new Process($args, base_path(), array_merge($_ENV, [
            'TOSHI_ADVERSARIAL_LIVE' => '1',
        ]), null, 600);

        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }

    private function liveGateEnabled(): bool
    {
        $value = env('TOSHI_ADVERSARIAL_LIVE', false);

        return $value === true || $value === 1 || $value === '1';
    }
}
