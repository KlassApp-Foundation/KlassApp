<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class CreateSubjectTool implements Tool
{
    public function description(): string
    {
        return 'Create a new subject. Provide name (required) and optionally a subject code.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Subject name, e.g. "Mathematics"'),
            'code' => $schema->string()->description('Subject code, e.g. "MATH"')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::createSubject($user, $request->all());
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
