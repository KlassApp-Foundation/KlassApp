<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Skills\TeacherSkill;

class RouteToTeacherSkillTool implements Tool
{
    public function description(): string
    {
        return 'Route a teacher-related query to the Teacher skill. Handles: adding teachers, assigning to subjects/classes, promoting to co-admin, listing teachers. Call this when the user asks about teachers, staff, instructors, or co-admins.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The original user query to route to the Teacher skill'),
        ];
    }

    public function handle(Request $request): string
    {
        $skill = new TeacherSkill;
        return $skill->prompt($request->get('query'))->text;
    }
}
