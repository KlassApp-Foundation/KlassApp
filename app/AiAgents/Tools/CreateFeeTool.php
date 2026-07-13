<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class CreateFeeTool implements Tool
{
    public function description(): string
    {
        return 'Create a fee category. Provide name, amount, and optionally: level, class, term name.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Fee name, e.g. "Tuition Fees"'),
            'amount' => $schema->number()->min(0)->description('Amount in UGX'),
            'level' => $schema->string()->description('Level, e.g. "Primary" or "Secondary"')->nullable(),
            'class' => $schema->string()->description('Class name')->nullable(),
            'term_name' => $schema->string()->description('Term name, e.g. "Term 1"')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::createFee($user, $request->all());
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
