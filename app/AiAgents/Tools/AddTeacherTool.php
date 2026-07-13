<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class AddTeacherTool implements Tool
{
    public function description(): string
    {
        return 'Add a new teacher to the school. Provide name (required), and optionally: email, phone, subjects (comma-separated), classes (comma-separated).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Teacher full name'),
            'email' => $schema->string()->description('Teacher email address')->nullable(),
            'phone' => $schema->string()->description('Phone number starting with +256')->nullable(),
            'subjects' => $schema->string()->description('Comma-separated subject names')->nullable(),
            'classes' => $schema->string()->description('Comma-separated class names')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::addTeacher($user, $request->all());
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
