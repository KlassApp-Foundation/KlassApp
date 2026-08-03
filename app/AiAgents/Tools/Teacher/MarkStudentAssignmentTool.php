<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\TeacherActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class MarkStudentAssignmentTool implements Tool
{
    use AuthorizesTeacherToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Mark a student assignment submission with obtained marks. Requires studentAssignment-review.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'student_assignment_id' => $schema->integer()->required(),
            'obtained_marks' => $schema->number()->required(),
            'comments' => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = [
            'student_assignment_id' => $request->get('student_assignment_id'),
            'obtained_marks' => $request->get('obtained_marks'),
            'comments' => $request->get('comments'),
        ];

        $confirm = $this->confirmOrExecute('toolTeacherMarkStudentAssignment', $args,
            fn () => 'Mark student assignment #'.$args['student_assignment_id'].' with '.$args['obtained_marks']);
        if ($confirm !== null) {
            return $confirm;
        }

        $result = TeacherActionService::markStudentAssignment($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
