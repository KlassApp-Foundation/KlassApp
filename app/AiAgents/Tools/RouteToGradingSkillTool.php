<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Skills\GradingSkill;

class RouteToGradingSkillTool implements Tool
{
    use AuthorizesToshiAction;
    public function description(): string
    {
        return 'Route a grading query to the Grading skill. Handles: setting grading scales, viewing grading scales, seeding default UNEB grading. Call this when the user asks about grading scales, grade boundaries, or marking schemes.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The original user query to route to the Grading skill'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $skill = new GradingSkill;
        return $skill->prompt($request->get('query'))->text;
    }
}
