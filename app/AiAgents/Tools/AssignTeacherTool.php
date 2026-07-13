<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class AssignTeacherTool implements Tool
{
    public function description(): string
    {
        return 'Assign a teacher to a subject and class. Provide teacher email, subject name, and class name.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'teacher_email' => $schema->string()->description('Teacher email address'),
            'subject_name' => $schema->string()->description('Subject name'),
            'class_name' => $schema->string()->description('Class name'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::assignTeacher($user, [
            'teacher_email' => $request->get('teacher_email'),
            'subject_name' => $request->get('subject_name'),
            'class_name' => $request->get('class_name'),
        ]);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
