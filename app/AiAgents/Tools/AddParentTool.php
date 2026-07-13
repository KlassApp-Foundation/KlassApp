<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class AddParentTool implements Tool
{
    public function description(): string
    {
        return 'Add a parent/guardian and link them to a student. Provide parent name, phone number starting with +256, and optionally the student ID to link to.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Parent full name'),
            'phone' => $schema->string()->description('Phone number starting with +256'),
            'studentId' => $schema->integer()->description('Student ID to link this parent to')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::addParent($user, [
            'name' => $request->get('name'),
            'phone' => $request->get('phone'),
            'student_id' => $request->get('studentId', 0),
        ]);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
