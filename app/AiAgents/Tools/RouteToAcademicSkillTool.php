<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Skills\AcademicSkill;

class RouteToAcademicSkillTool implements Tool
{
    public function description(): string
    {
        return 'Route an academic structure query to the Academic skill. Handles: listing classes and sections, creating terms, subjects, and exams. Call this when the user asks about classes, sections, terms, subjects, exams, or academic year setup.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The original user query to route to the Academic skill'),
        ];
    }

    public function handle(Request $request): string
    {
        $skill = new AcademicSkill;
        return $skill->prompt($request->get('query'))->text;
    }
}
