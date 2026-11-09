<?php

namespace App\AiAgents;

use App\AiAgents\Concerns\UsesToshiLlm;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/**
 * One chat completion. This agent has no tools.
 *
 * Gemini 3.5 Flash thinks at medium effort by default, and that did not
 * return inside the 5 second limit. minimal is the lowest effort the
 * OpenAI-compatible API accepts for this model.
 */
#[MaxTokens(180)]
#[Timeout(5)]
class ChecklistFactsAgent implements Agent, HasProviderOptions
{
    use Promptable;
    use UsesToshiLlm;

    public function __construct(private string $factsJson) {}

    public function providerOptions(Lab|string $provider): array
    {
        return [
            'reasoning_effort' => 'minimal',
        ];
    }

    public function instructions(): string
    {
        return <<<TEXT
You are Toshi, talking with a school admin during checklist onboarding.
Answer in one or two short sentences.
Use only the JSON facts below. Every number and every name must come from those facts.
If the facts do not cover the question, say you don't know that from this checklist.
Do not claim that anything was saved, created, or changed.
If a step is waiting, name its button and tell the admin to use that button.
Facts:
{$this->factsJson}
TEXT;
    }
}
