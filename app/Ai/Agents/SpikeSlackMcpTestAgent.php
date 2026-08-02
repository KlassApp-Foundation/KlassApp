<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Mcp\Facades\Mcp;
use Throwable;

/**
 * Throwaway test agent for the Slack MCP client spike.
 * Intentionally NOT an OperationsAgent — no role routing, no production tools.
 * Agent-mediated MCP tools audit via LogToolInvoked; MCP write HITL deferred.
 */
#[MaxSteps(3)]
#[Timeout(60)]
class SpikeSlackMcpTestAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
You are a throwaway KlassApp spike agent used only to verify that MCP Slack
tools register into laravel/ai. Prefer read-only tools (auth test / list channels).
Do not invent Slack data — call the MCP tools.
PROMPT;
    }

    /**
     * @return iterable<int, mixed>
     */
    public function tools(): iterable
    {
        try {
            return [
                ...Mcp::client('slack')->tools(),
            ];
        } catch (Throwable $e) {
            // Agent construction / tool listing must not fatally break the spike
            // when credentials are missing — the artisan probe reports the gap.
            report($e);

            return [];
        }
    }
}
