<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class CreateExamTool implements Tool
{
    public function description(): string
    {
        return 'Create an exam. Provide name (required), and optionally: subject, class/section, type (e.g. "Mid Term", "End of Term", "Mock"), term (e.g. "Term 1"), scheduled_at (Y-m-d).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Exam name, e.g. "End of Term 1 Mathematics"'),
            'subject' => $schema->string()->description('Subject name, e.g. "Mathematics"')->nullable(),
            'class' => $schema->string()->description('Class/section name')->nullable(),
            'type' => $schema->string()->description('Exam type, e.g. "End of Term", "Mid Term", "Mock"')->nullable(),
            'term' => $schema->string()->description('Term name, e.g. "Term 1"')->nullable(),
            'scheduled_at' => $schema->string()->description('Date Y-m-d')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::createExam($user, $request->all());
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
