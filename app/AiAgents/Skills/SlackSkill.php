<?php

namespace App\AiAgents\Skills;

use App\AiAgents\Concerns\UsesToshiLlm;
use App\Ai\Tools\Toshi\ApprovableMcpTool;
use App\Exceptions\ConnectorNotConnected;
use App\Services\Toshi\McpWriteGate;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Facades\Mcp;
use Throwable;

/**
 * Slack connector skill — read + write via MCP client.
 *
 * Discover tools from the named MCP client and wrap every primitive with
 * ApprovableMcpTool so write-classified tools pause for human approval.
 * Reads execute immediately with audit-only.
 *
 * Invoked via RouteToSlackSkillTool (custom Tool wrapper), not as an SDK
 * Sub-Agent (does not implement CanActAsTool).
 *
 * Conversational + RemembersConversations: REQUIRED for native laravel/ai
 * HITL approval pause/resume (GeneratesText::throwIfNotResumable throws
 * ApprovalNotResumableException for non-Conversational agents). The paused
 * turn persists approval_state on agent_conversation_messages; RouteTo*
 * surfaces the pause to the Toshi panel, which resumes via
 * continue(conversationId)->prompt(Decisions::...).
 *
 * UsesToshiLlm: this skill calls prompt() itself, so it must resolve the same
 * openai-compatible provider/model as ToshiOrchestrator.
 */
#[MaxSteps(5)]
class SlackSkill implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;
    use UsesToshiLlm;

    public function instructions(): string
    {
        $catalog = config('toshi.mcp_connectors.slack');
        $enabled = $catalog['enabled'] ?? false;

        if (! $enabled) {
            return 'The Slack connector is disabled on this KlassApp instance.';
        }

        return <<<'PROMPT'
You manage the school's Slack workspace through MCP tools.

Read tools (execute immediately):
- list channels
- search messages across channels (use sparingly, returns many results)
- get recent messages from a specific channel

Write tools (require human approval before executing):
- post a message to a channel

Rules:
- Only call tools actually exposed in your tool list; do not invent tools or channel names.
- Tool names are exposed exactly as provided — some environments prefix them (e.g. mcp_tools_…); always use the exact exposed name.
- For searches, prefer narrow queries (specific channel + date) to avoid noise.
- Before posting, confirm the target channel exists via the channel-list tool.
- If the Slack workspace is not connected, tell the user to connect it in School Settings → Integrations.
- If a tool call pauses for approval, tell the user approval is required and stop — do not retry.
- Be concise. Summarize channel lists and message history in plain language.
PROMPT;
    }

    /**
     * @return iterable<int, Tool>
     */
    public function tools(): iterable
    {
        $catalog = config('toshi.mcp_connectors.slack');

        if (! ($catalog['enabled'] ?? false)) {
            return [];
        }

        try {
            $primitives = Mcp::client('slack')->tools();
        } catch (ConnectorNotConnected) {
            return [
                new class implements Tool
                {
                    public function name(): string
                    {
                        return 'slack_status';
                    }

                    public function description(): string
                    {
                        return 'Slack is not connected for this school. Ask the admin to connect it in School Settings → Integrations.';
                    }

                    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
                    {
                        return [];
                    }

                    public function handle(Request $request): string
                    {
                        return 'Slack is not connected. Go to School Settings → Integrations → Slack to connect your workspace.';
                    }
                },
            ];
        } catch (Throwable $e) {
            report($e);

            return [];
        }

        $wrapped = [];

        foreach ($primitives as $primitive) {
            if (! \Laravel\Ai\Tools\McpTool::supports($primitive)) {
                continue;
            }

            $wrapped[] = ApprovableMcpTool::wrap('slack', $primitive);
        }

        return $wrapped;
    }
}
