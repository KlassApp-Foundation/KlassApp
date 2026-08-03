<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\SchoolAcademicsOpsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateStudentHomeworkTool implements Tool
{
    use AuthorizesTeacherToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Mark a student homework submission checked. Requires studentHomework-review.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'student_homework_id' => $schema->integer()->required(),
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
            'student_homework_id' => $request->get('student_homework_id'),
            'comments' => $request->get('comments'),
        ];

        $confirm = $this->confirmOrExecute('toolTeacherUpdateStudentHomework', $args,
            fn () => 'Check student homework #'.$args['student_homework_id']);
        if ($confirm !== null) {
            return $confirm;
        }

        $result = SchoolAcademicsOpsActionService::updateStudentHomework($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
