<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Skills\SlackSkill;
use App\Exceptions\ToshiUnauthorizedActionException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Exceptions\ApprovalNotResumableException;
use Laravel\Ai\Tools\Request;

/**
 * Route a Slack workspace query to the Slack skill.
 *
 * Same pattern as RouteToSchoolCommsSkillTool / RouteToAcademicSkillTool:
 * a plain Tool that gates auth, then manually `(new SlackSkill)->prompt($query)`.
 *
 * Handles: listing channels, searching messages, reading channel history,
 * and posting messages (the latter gated by ApprovableMcpTool / HITL approval).
 *
 * Write pauses: SlackSkill is Conversational; a write-classified tool call
 * pauses the nested prompt() with pending approvals instead of executing.
 * Because the skill is invoked inline (not as a conversation-scoped agent
 * turn), a pause cannot be resumed from this tool — we translate the paused
 * AgentResponse into a ToshiActionService payload so the Toshi panel can
 * render an approval card and resume natively via
 * continue(conversationId)->prompt(Decisions::...).
 */
class RouteToSlackSkillTool implements Tool
{
    use AuthorizesToshiAction;

    public function description(): string
    {
        return 'Route a Slack workspace query to the Slack skill. '
            .'Handles: listing channels, searching messages, reading channel history, '
            .'and posting messages (writes require human approval). '
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
            // Fail closed with an actionable message rather than a raw crash.
            report($e);

            return '❌ A Slack write needs approval, but the approval could not be set up. Please retry the request.';
        }

        if ($response->hasPendingApprovals()) {
            $first = $response->pendingApprovals->first();

            // Surface the pause to the panel via the existing side-channel
            // (same mechanism ToshiSdkV2Service reads for __tier2_confirm).
            \App\Services\ToshiActionService::$pendingConfirmPayload = [
                'tool' => $first->tool,
                'args' => $first->arguments,
                'preview' => 'Approve Slack write: '.$first->tool
                    .' '.json_encode($first->arguments, JSON_UNESCAPED_SLASHES)
                    .($first->reason !== null && $first->reason !== '' ? ' — '.$first->reason : ''),
                'mcp_resume' => [
                    'agent_class' => SlackSkill::class,
                    'conversation_id' => $response->conversationId,
                    'approval_id' => $first->id,
                ],
            ];

            return json_encode([
                '__tier2_confirm' => true,
                'tool' => $first->tool,
                'args' => $first->arguments,
                'preview' => \App\Services\ToshiActionService::$pendingConfirmPayload['preview'],
            ]);
        }

        return $response->text;
    }
}
