<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Skills\SchoolCommsSkill;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Custom orchestrator router — NOT Laravel AI Sub-Agents / CanActAsTool.
 *
 * Same pattern as RouteToAcademicSkillTool / RouteToFeeSkillTool / etc.:
 * a plain Tool that gates auth, then manually `(new SchoolCommsSkill)->prompt($query)`.
 * The SDK Sub-Agent path would instead return `new SchoolCommsSkill` from
 * ToshiOrchestrator::tools() so resolveTool() wraps it in AgentTool (task schema).
 * We keep the custom RouteTo* Tool for AuthorizesToshiAction + `query` schema parity
 * with the rest of the Orchestrator skill routers.
 *
 * Leaf writes still use ConfirmsBeforeWrite (`toolCreateNotice`, …). Nested
 * `$skill->prompt()` fires ToolInvoked for the leaf tool; Tier-2 confirmYes
 * audits via ToshiAuditService with the leaf tool key + acting_user_id.
 */
class RouteToSchoolCommsSkillTool implements Tool
{
    use AuthorizesToshiAction;

    public function description(): string
    {
        return 'Route a school communications query to the School Comms skill. Handles: notices/noticeboard, calendar events, and holidays — list, create, and update only (no destroy). Call when the user asks about notices, noticeboard, events, or holidays.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The original user query to route to the School Comms skill'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $skill = new SchoolCommsSkill;

        return $skill->prompt($request->get('query'))->text;
    }
}
