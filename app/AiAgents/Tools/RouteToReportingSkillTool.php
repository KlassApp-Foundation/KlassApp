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
        return 'Route a reporting or marks-read query to the Reporting skill. Handles: academic, fee, attendance, and summary reports; missing or unapproved marks for a class and exam set; a learner\'s report status; a draft reminder to the class teacher (nothing is sent). Call this when the user asks for reports, analytics, missing marks, report status, or a reminder about marks.';
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
