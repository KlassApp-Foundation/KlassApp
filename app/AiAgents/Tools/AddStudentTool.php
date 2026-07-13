<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class AddStudentTool implements Tool
{
    public function description(): string
    {
        return 'Add a new student to the school. Provide name (required), class/standard (required), and optionally stream, type, parent name, parent phone.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Student full name'),
            'class' => $schema->string()->description('Class/standard name, e.g. "Primary Four" or "Senior One"'),
            'stream' => $schema->string()->description('Stream/section name, e.g. "A" or "Science"')->nullable(),
            'type' => $schema->string()->description('Student type (regular, boarder, day)')->nullable(),
            'parent' => $schema->string()->description('Parent/guardian full name')->nullable(),
            'parentPhone' => $schema->string()->description('Parent phone number starting with +256')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::addStudent($user, $request->all());
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
