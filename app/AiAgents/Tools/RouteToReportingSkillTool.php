<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Skills\ReportingSkill;

class RouteToReportingSkillTool implements Tool
{
    use AuthorizesToshiAction;
    public function description(): string
    {
        return 'Route a reporting/analytics query to the Reporting skill. Handles: generating academic, fee, attendance, and summary reports. Call this when the user asks for reports, analytics, breakdowns, summaries, or data exports.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The original user query to route to the Reporting skill'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $skill = new ReportingSkill;
        return $skill->prompt($request->get('query'))->text;
    }
}
