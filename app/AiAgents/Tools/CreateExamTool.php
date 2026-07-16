<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\Academics\Exam;

class CreateExamTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

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
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $args = $request->all();

        $confirm = $this->confirmOrExecute('toolCreateExam', $args,
            fn() => "Create exam: {$args['name']}"
                . (!empty($args['subject']) ? ", subject: {$args['subject']}" : '')
                . (!empty($args['class']) ? ", class: {$args['class']}" : ''));
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::createExam($user, $args);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $name = trim($request->get('name', ''));
        if ($name === '') {
            return ['verified' => false, 'message' => 'Exam name is required for verification.'];
        }

        $exists = Exam::where('school_id', $schoolId)
            ->where('name', $name)
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Exam confirmed in database.'
                : 'Exam was not found after creation — it may not have persisted.',
        ];
    }
}
