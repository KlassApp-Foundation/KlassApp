<?php

namespace App\Console\Commands;

use App\AiAgents\ToshiLlm;
use App\Exceptions\AmbiguousToshiLlmConfigException;
use App\Services\Toshi\LiveAdversarialRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Manual / gated runner for live-LLM adversarial soft-refusal checks.
 *
 * Requires TOSHI_ADVERSARIAL_LIVE=1 and a real openai-compatible API key.
 * Runs scenarios in-process (no PHPUnit) on an isolated sqlite :memory: DB —
 * safe for --no-dev production images. Does not send WhatsApp to real parents
 * (Http::fake for Evolution/WhatsApp hosts only).
 */
class ToshiAdversarialLiveCommand extends Command
{
    protected $signature = 'toshi:adversarial-live
                            {--filter= : Optional scenario id substring filter}
                            {--scheduled : Quiet no-op when gate/keys missing (for Kernel schedule)}';

    protected $description = 'Run live-LLM Toshi adversarial soft-refusal suite in-process (no PHPUnit)';

    public function handle(LiveAdversarialRunner $runner): int
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

        try {
            ToshiLlm::assertConfigConsistent();
        } catch (AmbiguousToshiLlmConfigException $e) {
            return $this->failCritical('config_conflict', $e->getMessage(), $e->context());
        }

        $this->info('Toshi live adversarial runner (in-process)');
        $this->line('  Provider : '.ToshiLlm::provider());
        $this->line('  URL      : '.ToshiLlm::url());
        $this->line('  Host     : '.ToshiLlm::urlHost());
        $this->line('  Model    : '.ToshiLlm::model());
        $this->line('  Key      : set ('.strlen($key).' chars)');
        $this->line('  App DB   : '.config('database.default').' (boot only — scenarios use sqlite :memory:)');
        $this->line('  Scenario DB : sqlite :memory: (production MySQL never written)');
        $this->line('  WhatsApp : Http::fake for Evolution/WhatsApp hosts — no real parent messages');
        $this->line('  PHPUnit  : not required');
        $this->newLine();

        $filter = $this->option('filter');
        $filter = is_string($filter) && $filter !== '' ? $filter : null;

        try {
            $result = $runner->run($filter, function (array $row): void {
                $this->line(sprintf(
                    '[live-adv] %-28s %-5s p=%d c=%d | %s',
                    $row['id'],
                    strtoupper($row['verdict']),
                    $row['prompt_tokens'],
                    $row['completion_tokens'],
                    $row['preview']
                ));
            });
        } catch (Throwable $e) {
            return $this->failCritical('runner_error', $e->getMessage(), [
                'exception' => $e::class,
                'provider' => ToshiLlm::provider(),
                'model' => ToshiLlm::model(),
                'url_host' => ToshiLlm::urlHost(),
            ]);
        }

        $totalTokens = $result['prompt_tokens'] + $result['completion_tokens'];
        $this->newLine();
        $this->info('=== Live adversarial summary ===');
        $this->line(sprintf(
            'scenarios=%d pass=%d flag=%d fail=%d tokens=%d (prompt=%d completion=%d) est_usd≈$%.4f',
            count($result['report']),
            $result['pass'],
            $result['flag'],
            $result['fail'],
            $totalTokens,
            $result['prompt_tokens'],
            $result['completion_tokens'],
            $result['estimated_usd']
        ));
        $this->line('provider='.$result['provider'].' host='.$result['host'].' model='.$result['model']);

        foreach ($result['report'] as $row) {
            $this->line(sprintf(
                ' - [%s] %s (%s): %s',
                strtoupper($row['verdict']),
                $row['id'],
                $row['role'],
                implode('; ', $row['notes'])
            ));
        }

        if (count($result['report']) === 0) {
            $this->warn('No scenarios matched the filter — nothing was scored.');

            return self::SUCCESS;
        }

        if ($result['ok']) {
            $this->info('Live adversarial suite passed (flags are warnings only).');

            return self::SUCCESS;
        }

        $failures = array_values(array_filter(
            $result['report'],
            fn (array $row): bool => $row['verdict'] === 'fail'
        ));

        return $this->failCritical(
            'soft_refusal_failures',
            'Live adversarial hard failures: '.count($failures),
            [
                'provider' => $result['provider'],
                'model' => $result['model'],
                'url_host' => $result['host'],
                'fail' => $result['fail'],
                'flag' => $result['flag'],
                'pass' => $result['pass'],
                'failures' => array_map(
                    fn (array $row): string => $row['id'].': '.implode('; ', $row['notes']),
                    $failures
                ),
            ]
        );
    }

    private function liveGateEnabled(): bool
    {
        $value = env('TOSHI_ADVERSARIAL_LIVE', false);

        return $value === true || $value === 1 || $value === '1';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function failCritical(string $reason, string $message, array $context = []): int
    {
        $payload = array_merge(['reason' => $reason], $context);

        try {
            $payload['checksum'] = ToshiLlm::configChecksum();
        } catch (Throwable) {
            // Intentionally empty — conflict already reported in $context.
        }

        Log::critical('Toshi live adversarial FAILED: '.$message, $payload);

        $this->error('CRITICAL: '.$message);
        $this->line('  Reason: '.$reason);
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $this->line('  '.$key.': '.(string) $value);
            } elseif (is_array($value)) {
                $this->line('  '.$key.': '.implode(' | ', array_map('strval', $value)));
            }
        }

        return self::FAILURE;
    }
}
