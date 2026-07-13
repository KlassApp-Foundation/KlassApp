<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Skills\FeeSkill;

class RouteToFeeSkillTool implements Tool
{
    public function description(): string
    {
        return 'Route a fee/payment query to the Fee skill. Handles: creating fee categories, recording payments, checking fee balances. Call this when the user asks about fees, payments, balances, invoices, or school fees.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The original user query to route to the Fee skill'),
        ];
    }

    public function handle(Request $request): string
    {
        $skill = new FeeSkill;
        return $skill->prompt($request->get('query'))->text;
    }
}
