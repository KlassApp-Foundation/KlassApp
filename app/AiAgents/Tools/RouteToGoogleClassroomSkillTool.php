<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Skills\GoogleClassroomSkill;
use App\Exceptions\ToshiUnauthorizedActionException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Exceptions\ApprovalNotResumableException;
use Laravel\Ai\Tools\Request;

/**
 * Route a Google Classroom query to the Classroom skill.
 *
 * Same pattern as RouteToSlackSkillTool / RouteToSchoolCommsSkillTool /
 * RouteToAcademicSkillTool: a plain Tool that gates auth, then manually
 * (new GoogleClassroomSkill)->prompt($query).
 *
 * Handles: listing courses and coursework with due dates.
 * Wave-1 is read-only — no write tools exist, so no HITL approval pause.
 *
 * Approval safety (defensive): GoogleClassroomSkill is Conversational,
 * so if a future wave added a write tool, a write-classified tool call
 * would pause the nested prompt() with pending approvals instead of
 * executing. The write-tools-empty catalog means that cannot happen today,
 * but the pause/resume path exists exactly as SlackSkill's.
 */
class RouteToGoogleClassroomSkillTool implements Tool
{
    use AuthorizesToshiAction;

    public function description(): string
    {
        return 'Route a Google Classroom query to the Classroom skill. '
            .'Handles: listing courses and coursework with due dates (read-only wave-1). '
            .'Call when the user asks about their Google Classroom courses or what is due.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The original user query to route to the Classroom skill'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $skill = (new GoogleClassroomSkill)->forUser($user);

        try {
            $response = $skill->prompt($request->get('query'));
        } catch (ApprovalNotResumableException $e) {
            // Defensive: GoogleClassroomSkill is Conversational, so this should
            // not fire for wave-1 (zero write tools). If a future wave adds a
            // write tool that triggers a pause but the resume path is somehow
            // broken, fail closed with an actionable message.
            report($e);

            return '❌ A Classroom action needs approval, but the approval could not be set up. Please retry the request.';
        }

        if ($response->hasPendingApprovals()) {
            $first = $response->pendingApprovals->first();

            // Surface the pause to the panel via the existing side-channel
            // (same mechanism ToshiSdkV2Service reads for __tier2_confirm).
            \App\Services\ToshiActionService::$pendingConfirmPayload = [
                'tool' => $first->tool,
                'args' => $first->arguments,
                'preview' => 'Approve Classroom action: '.$first->tool
                    .' '.json_encode($first->arguments, JSON_UNESCAPED_SLASHES)
                    .($first->reason !== null && $first->reason !== '' ? ' — '.$first->reason : ''),
                'mcp_resume' => [
                    'agent_class' => GoogleClassroomSkill::class,
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
