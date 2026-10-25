<?php

namespace App\AiAgents;

use App\AiAgents\Concerns\UsesToshiLlm;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * One-shot connectivity probe for the provider ToshiLlm resolves.
 * No tools. Used by `toshi:llm-health --live` when the provider is Bedrock.
 */
class ToshiLlmProbe implements Agent
{
    use Promptable;
    use UsesToshiLlm;

    public function instructions(): string
    {
        return 'You are a connectivity probe. Reply with exactly the text the user requests and nothing else.';
    }
}
