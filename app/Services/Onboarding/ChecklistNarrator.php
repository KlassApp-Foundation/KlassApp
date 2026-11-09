<?php

namespace App\Services\Onboarding;

use App\AiAgents\ChecklistFactsAgent;
use App\AiAgents\ToshiLlm;
use App\Models\OnboardingPlan;
use App\Services\Toshi\ToshiRateLimit;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One chat call for a checklist question. No tools, no retry.
 * A missing key, a failure, a 5 second timeout, a made-up number,
 * or a 429 falls back to the caller. The 429 reply is one sentence.
 */
class ChecklistNarrator
{
    public function __construct(private ChecklistOnboardingService $plans) {}

    public function speak(string $question, OnboardingPlan $plan): ?string
    {
        if (! ToshiLlm::keyConfigured()) {
            return null;
        }

        $facts = $this->plans->narrationFacts($plan);
        $started = hrtime(true);

        try {
            $response = (new ChecklistFactsAgent($this->encode($facts)))
                ->prompt($question, timeout: 5);
            $text = trim($response->text);
            $ms = $this->elapsed($started);

            if ($text === '' || ! $this->acceptable($text, $facts)) {
                $this->log($plan, 'rejected', $ms);

                return null;
            }

            $this->log($plan, 'ok', $ms);

            return $this->shorten($text);
        } catch (Throwable $error) {
            $ms = $this->elapsed($started);
            if (ToshiRateLimit::isLimited($error)) {
                $this->log($plan, 'limited', $ms);

                return ToshiRateLimit::adminMessage();
            }

            $this->log($plan, $error instanceof ConnectionException ? 'timeout' : 'failed', $ms);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function acceptable(string $text, array $facts): bool
    {
        if (preg_match('/\b(i\'ve|i have|i just|i)\s+(created|saved|wrote|added|deleted|updated)\b/i', $text) === 1) {
            return false;
        }

        $haystack = $this->encode($facts);
        if (preg_match_all('/\d+/', $text, $found) === false || $found[0] === []) {
            return $found[0] === [];
        }

        foreach ($found[0] as $number) {
            if (preg_match('/(?<!\d)'.preg_quote($number, '/').'(?!\d)/', $haystack) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function encode(array $facts): string
    {
        return json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function shorten(string $text): string
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', $text) ?: [];
        $parts = array_values(array_filter($parts, fn (string $part): bool => trim($part) !== ''));
        if (count($parts) <= 2) {
            return $text;
        }

        return $parts[0].' '.$parts[1];
    }

    private function elapsed(int $started): int
    {
        return (int) ((hrtime(true) - $started) / 1_000_000);
    }

    private function log(OnboardingPlan $plan, string $outcome, int $ms): void
    {
        Log::info('checklist.narration', [
            'school_id' => $plan->school_id,
            'outcome' => $outcome,
            'ms' => $ms,
        ]);
    }
}
