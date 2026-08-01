<?php

namespace App\AiAgents\WhatsApp;

use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Proxies an OperationsAgent while structurally stripping write tools for WhatsApp v1.
 */
#[MaxSteps(5)]
#[Timeout(120)]
class WhatsAppReadOnlyAgent implements Agent, HasTools
{
    use Promptable;

    /**
     * @param  Agent&HasTools  $inner
     */
    public function __construct(
        private readonly Agent $inner,
        private readonly string $channelInstructionsSuffix = '',
    ) {
        if (! $inner instanceof HasTools) {
            throw new \InvalidArgumentException('WhatsAppReadOnlyAgent requires an agent with tools.');
        }
    }

    public function provider(): string
    {
        if (method_exists($this->inner, 'provider')) {
            return $this->inner->provider();
        }

        return 'openai-compatible';
    }

    public function model(): string
    {
        if (method_exists($this->inner, 'model')) {
            return $this->inner->model();
        }

        return config('toshi.model', 'nvidia/llama-3.3-nemotron-super-49b-v1');
    }

    public function instructions(): Stringable|string
    {
        $base = $this->inner->instructions();
        $suffix = $this->channelInstructionsSuffix !== ''
            ? $this->channelInstructionsSuffix
            : "\n\nWhatsApp channel constraint: you only have read-only tools. Do not attempt writes, payments, payroll, or impersonation.";

        return rtrim((string) $base)."\n".$suffix;
    }

    /**
     * @return iterable<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        /** @var HasTools $inner */
        $inner = $this->inner;

        return WhatsAppWriteExclusion::filterReadOnly($inner->tools());
    }

    public function run(string $query): ?string
    {
        try {
            if (method_exists($this->inner, 'run') && ! ($this->inner instanceof self)) {
                // Prefer filtered tools via this proxy's prompt, not inner->run.
            }

            $response = $this->prompt($query);

            return $response->text;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('WhatsAppReadOnlyAgent failed', [
                'inner' => $this->inner::class,
                'error' => $e->getMessage(),
                'query' => $query,
            ]);

            return null;
        }
    }

    public function innerAgentClass(): string
    {
        return $this->inner::class;
    }
}
