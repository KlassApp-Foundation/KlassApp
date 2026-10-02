<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Skills\SlackSkill;
use App\Exceptions\PendingMcpApprovalException;
use App\Exceptions\ToshiUnauthorizedActionException;
use App\Services\ToshiActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Exceptions\ApprovalNotResumableException;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Tools\Request;

/**
 * Route a Slack workspace query to the Slack skill.
 *
 * Same pattern as RouteToSchoolCommsSkillTool / RouteToAcademicSkillTool:
 * a plain Tool that gates auth, then manually `(new SlackSkill)->prompt($query)`.
 *
 * Write pauses: SlackSkill is Conversational; a write-classified tool call
 * pauses the nested prompt() with pending approvals instead of executing.
 *
 * Nested-pause surfacing (structural): returning a JSON string to the parent
 * Orchestrator lets the outer LLM continue past the pause — on some providers
 * that continuation fails and ToshiSdkV2Service falls through to
 * fallbackMessage(), leaving a stranded pending_approval with no confirm card.
 * After populating the side-channel we throw PendingMcpApprovalException so the
 * parent loop aborts immediately; ToshiSdkV2Service turns the side-channel into
 * the panel __tier2_confirm payload.
 */
class RouteToSlackSkillTool implements Tool
{
    use AuthorizesToshiAction;

    public function description(): string
    {
        return 'Route a Slack workspace query to the Slack skill. '
            .'Handles: searching channels, searching public messages, reading channel/thread history, '
            .'and sending/scheduling/drafting messages (writes require human approval). '
            .'Call when the user asks about Slack channels, Slack messages, or posting to Slack.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The original user query to route to the Slack skill'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $skill = (new SlackSkill)->forUser($user);

        try {
            $response = $skill->prompt($request->get('query'));
        } catch (ApprovalNotResumableException $e) {
            // Defensive: SlackSkill is Conversational, so this should not fire.
            report($e);

            return '❌ A Slack write needs approval, but the approval could not be set up. Please retry the request.';
        }

        if ($response->hasPendingApprovals()) {
            $this->surfacePendingApproval($response);

            throw new PendingMcpApprovalException(
                'Slack MCP tool paused for human approval.'
            );
        }

        return $response->text;
    }

    /**
     * Populate the panel side-channel so ToshiSdkV2Service can return
     * __tier2_confirm even when the Orchestrator swallows this tool's throw.
     */
    private function surfacePendingApproval(AgentResponse $response): void
    {
        $first = $response->pendingApprovals->first();

        ToshiActionService::$pendingConfirmPayload = [
            'tool' => $first->tool,
            'args' => $first->arguments,
            'preview' => 'Approve Slack action: '.$first->tool
                .' '.json_encode($first->arguments, JSON_UNESCAPED_SLASHES)
                .($first->reason !== null && $first->reason !== '' ? ' — '.$first->reason : ''),
            'mcp_resume' => [
                'agent_class' => SlackSkill::class,
                'conversation_id' => $response->conversationId,
                'approval_id' => $first->id,
            ],
        ];
    }
}
