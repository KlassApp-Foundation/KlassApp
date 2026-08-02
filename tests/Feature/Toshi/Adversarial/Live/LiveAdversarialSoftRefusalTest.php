<?php

namespace Tests\Feature\Toshi\Adversarial\Live;

use App\Services\Toshi\LiveAdversarialRunner;
use Tests\TestCase;

/**
 * Live-LLM soft-refusal spot checks via the in-process runner.
 *
 * @group live-llm
 *
 * Not merge-blocking. Requires TOSHI_ADVERSARIAL_LIVE=1 and a real
 * openai-compatible key. Runner uses sqlite :memory: + Http::fake (no prod DB, no WA).
 */
class LiveAdversarialSoftRefusalTest extends TestCase
{
    public function test_live_adversarial_suite_soft_refusals_and_no_mutations(): void
    {
        if (! $this->liveGateEnabled()) {
            $this->markTestSkipped('Set TOSHI_ADVERSARIAL_LIVE=1 to run live-LLM adversarial checks.');
        }

        if (empty(config('ai.providers.openai-compatible.key'))) {
            $this->markTestSkipped('openai-compatible API key missing — cannot run live-LLM suite.');
        }

        $result = app(LiveAdversarialRunner::class)->run(null, function (array $row): void {
            fwrite(STDERR, sprintf(
                "[live-adv] %-28s %-5s p=%d c=%d | %s\n",
                $row['id'],
                strtoupper($row['verdict']),
                $row['prompt_tokens'],
                $row['completion_tokens'],
                $row['preview']
            ));
        });

        $totalTokens = $result['prompt_tokens'] + $result['completion_tokens'];
        fwrite(STDERR, "\n=== Live adversarial summary ===\n");
        fwrite(STDERR, sprintf(
            "scenarios=%d pass=%d flag=%d fail=%d tokens=%d (prompt=%d completion=%d) est_usd≈$%.4f\n",
            count($result['report']),
            $result['pass'],
            $result['flag'],
            $result['fail'],
            $totalTokens,
            $result['prompt_tokens'],
            $result['completion_tokens'],
            $result['estimated_usd']
        ));
        fwrite(STDERR, 'provider='.$result['provider'].' host='.$result['host'].' model='.$result['model']."\n");
        foreach ($result['report'] as $row) {
            fwrite(STDERR, sprintf(
                " - [%s] %s (%s): %s\n",
                strtoupper($row['verdict']),
                $row['id'],
                $row['role'],
                implode('; ', $row['notes'])
            ));
        }
        fwrite(STDERR, "================================\n\n");

        $failures = array_values(array_filter(
            $result['report'],
            fn (array $row): bool => $row['verdict'] === 'fail'
        ));

        $this->assertSame(
            [],
            array_map(fn (array $row): string => $row['id'].': '.implode('; ', $row['notes']), $failures),
            'Live adversarial hard failures (false-success / leak / mutation / empty). Flags are warnings only.'
        );
    }

    private function liveGateEnabled(): bool
    {
        $value = env('TOSHI_ADVERSARIAL_LIVE', false);

        return $value === true || $value === 1 || $value === '1';
    }
}
