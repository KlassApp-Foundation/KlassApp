<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Skills\StudentSkill;

class RouteToStudentSkillTool implements Tool
{
    use AuthorizesToshiAction;
    public function description(): string
    {
        return 'Route a student-related query to the Student skill. Handles: adding/finding students, attendance, exam marks, parent management, enrollment counts. Call this when the user asks about students, learners, pupils, attendance, marks, or parents.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The original user query to route to the Student skill'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $skill = new StudentSkill;
        return $skill->prompt($request->get('query'))->text;
    }
}
