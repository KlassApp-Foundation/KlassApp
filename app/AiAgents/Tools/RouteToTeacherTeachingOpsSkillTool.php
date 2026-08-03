<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\AiAgents\Skills\TeacherTeachingOpsSkill;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Custom Teacher router — NOT Laravel AI Sub-Agents / CanActAsTool.
 * Same pattern as RouteToSchoolAcademicsOpsSkillTool.
 */
class RouteToTeacherTeachingOpsSkillTool implements Tool
{
    use AuthorizesTeacherToshiAction;

    public function description(): string
    {
        return 'Route a teaching-ops query to the Teacher Teaching Ops skill. Handles: student homework review (list/show/check), student assignment review/marking, and own leave list/show/cancel. No peer leave approve, no destroy. Call when the teacher asks about checking homework submissions, marking assignments, or their leave status.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The original user query to route to the Teacher Teaching Ops skill'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $skill = new TeacherTeachingOpsSkill;

        return $skill->prompt($request->get('query'))->text;
    }
}
