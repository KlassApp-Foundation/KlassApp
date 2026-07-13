<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class EnterMarkTool implements Tool
{
    public function description(): string
    {
        return 'Enter an exam mark for a student. Provide student ID, exam ID, and marks (score 0-100).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'studentId' => $schema->integer()->description('Student user ID'),
            'examId' => $schema->integer()->description('Exam ID'),
            'marks' => $schema->number()->min(0)->max(100)->description('Score 0-100'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::enterMark($user, [
            'student_id' => $request->get('studentId'),
            'exam_id' => $request->get('examId'),
            'marks' => $request->get('marks'),
        ]);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
