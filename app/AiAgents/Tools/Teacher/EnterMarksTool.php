<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\TeacherActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class EnterMarksTool implements Tool
{
    use AuthorizesTeacherToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Enter exam marks for a student on an exam assigned to you as teacher.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'exam_id' => $schema->integer()->required(),
            'student' => $schema->string()->required(),
            'marks' => $schema->number()->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = ['exam_id' => (int) $request->get('exam_id'), 'student' => $request->get('student'), 'marks' => $request->get('marks')];

        $confirm = $this->confirmOrExecute('toolTeacherEnterMarks', $args, fn () => "Enter marks {$args['marks']} for {$args['student']} on exam #{$args['exam_id']}");
        if ($confirm !== null) {
            return $confirm;
        }
        $result = TeacherActionService::enterMarks($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
