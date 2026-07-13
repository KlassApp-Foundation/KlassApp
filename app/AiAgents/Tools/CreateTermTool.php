<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class CreateTermTool implements Tool
{
    public function description(): string
    {
        return 'Create a new academic term. Provide name (e.g. "Term 1"), start date (Y-m-d), and end date (Y-m-d).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Term name, e.g. "Term 1"'),
            'start_date' => $schema->string()->description('Start date in Y-m-d format'),
            'end_date' => $schema->string()->description('End date in Y-m-d format'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::createTerm($user, [
            'name' => $request->get('name'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ]);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
