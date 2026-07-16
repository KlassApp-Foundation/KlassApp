<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\Academics\Marks;

class EnterMarkTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

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
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        // Accept both camelCase (from LLM schema) and snake_case (from stored args)
        $args = [
            'student_id' => $request->get('studentId') ?? $request->get('student_id'),
            'exam_id' => $request->get('examId') ?? $request->get('exam_id'),
            'marks' => $request->get('marks'),
        ];

        $confirm = $this->confirmOrExecute('toolEnterMark', $args,
            fn() => "Enter mark: student {$args['student_id']}, exam {$args['exam_id']}, score {$args['marks']}");
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::enterMark($user, $args);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $studentId = $request->get('studentId') ?? $request->get('student_id');
        $examId = $request->get('examId') ?? $request->get('exam_id');
        if (!$studentId || !$examId) {
            return ['verified' => false, 'message' => 'Student ID and exam ID are required for verification.'];
        }

        $exists = Marks::where('student_id', $studentId)
            ->where('exam_id', $examId)
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Mark record confirmed in database.'
                : 'Mark was not found after writing.',
        ];
    }
}
