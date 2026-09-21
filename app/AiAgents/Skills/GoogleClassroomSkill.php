<?php

namespace App\AiAgents\Skills;

use App\AiAgents\Concerns\UsesToshiLlm;
use App\Ai\Tools\Toshi\ApprovableMcpTool;
use App\Exceptions\ConnectorNotConnected;
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
 * Google Classroom connector skill — read-only wave-1 via MCP client.
 *
 * Discover tools from the named MCP client and wrap every primitive with
 * ApprovableMcpTool so the pattern is consistent with SlackSkill — even
 * though Classroom wave-1 has zero write tools today. If a future wave
 * adds a write tool, ApprovableMcpTool wrapping ensures it lands in
 * gated territory by default.
 *
 * Invoked via RouteToGoogleClassroomSkillTool (custom Tool wrapper), not
 * as an SDK Sub-Agent (does not implement CanActAsTool).
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
class GoogleClassroomSkill implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;
    use UsesToshiLlm;

    public function instructions(): string
    {
        $catalog = config('toshi.mcp_connectors.google-classroom');
        $enabled = $catalog['enabled'] ?? false;

        if (! $enabled) {
            return 'The Google Classroom connector is disabled on this KlassApp instance.';
        }

        return <<<'PROMPT'
You manage the school's Google Classroom connection through MCP tools.

Read tools (execute immediately):
- list courses (the connecting teacher's Classroom courses)
- list coursework and due dates for a specific course

Wave-1 is read-only: no write tools exist, so no action pauses for approval.

Rules:
- Only call tools actually exposed in your tool list; do not invent tools or course ids.
- Tool names are exposed exactly as provided — some environments prefix them (e.g. mcp_tools_…); always use the exact exposed name.
- When listing coursework, always provide a courseId from a previous courses list — never guess one.
- Coursework includes dueDate (year/month/day) and dueTime (hours/minutes/nanos); present these as human-readable dates.
- If Google Classroom is not connected, tell the user to connect it in School Settings → Integrations.
- Be concise. Summarize course lists and coursework in plain language.
PROMPT;
    }

    /**
     * @return iterable<int, Tool>
     */
    public function tools(): iterable
    {
        $catalog = config('toshi.mcp_connectors.google-classroom');

        if (! ($catalog['enabled'] ?? false)) {
            return [];
        }

        try {
            $primitives = Mcp::client('google-classroom')->tools();
        } catch (ConnectorNotConnected) {
            return [
                new class implements Tool
                {
                    public function name(): string
                    {
                        return 'google_classroom_status';
                    }

                    public function description(): string
                    {
                        return 'Google Classroom is not connected for this school. Ask the admin to connect it in School Settings → Integrations.';
                    }

                    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
                    {
                        return [];
                    }

                    public function handle(Request $request): string
                    {
                        return 'Google Classroom is not connected. Go to School Settings → Integrations → Google Classroom to connect.';
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

            // Wave-1 has zero write tools, but wrapping every primitive through
            // ApprovableMcpTool is deliberate: if a future wave adds a write tool,
            // it automatically lands in gated territory. ApprovableMcpTool checks
            // the catalog's write_tools list — for a tool that isn't classified as
            // a write, shouldRequestApproval() returns null and execution proceeds
            // immediately (audit-only), identical to calling the primitive directly.
            $wrapped[] = ApprovableMcpTool::wrap('google-classroom', $primitive);
        }

        return $wrapped;
    }
}
