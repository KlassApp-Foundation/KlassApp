<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Skills\SchoolAcademicsOpsSkill;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Custom orchestrator router — NOT Laravel AI Sub-Agents / CanActAsTool.
 *
 * Same pattern as RouteToSchoolCommsSkillTool: gate auth, then
 * `(new SchoolAcademicsOpsSkill)->prompt($query)`.
 */
class RouteToSchoolAcademicsOpsSkillTool implements Tool
{
    use AuthorizesToshiAction;

    public function description(): string
    {
        return 'Route an academic-ops query to the School Academics Ops skill. Handles: timetable slots (list/create/update), homework admin list/create/update/approve/reject, student-homework review (list/show/update), and per-student report-card PDF generation. No destroy. Call when the user asks about timetable, homework approval, checking student homework submissions, or generating a student report card.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The original user query to route to the School Academics Ops skill'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $skill = new SchoolAcademicsOpsSkill;

        return $skill->prompt($request->get('query'))->text;
    }
}
