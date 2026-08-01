<?php

namespace App\AiAgents\Tools\Student;

use App\AiAgents\Concerns\AuthorizesStudentToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\StudentActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SubmitAssignmentTool implements Tool
{
    use AuthorizesStudentToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Submit an assignment for the signed-in student only. Provide assignment_id from their class list and optional note. Do not pass student_id.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'assignment_id' => $schema->integer()->required(),
            'note' => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeStudentOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = [
            'assignment_id' => $request->get('assignment_id'),
            'note' => $request->get('note'),
        ];

        $confirm = $this->confirmOrExecute(
            'toolStudentSubmitAssignment',
            $args,
            fn () => "Submit assignment #{$args['assignment_id']}"
        );
        if ($confirm !== null) {
            return $confirm;
        }

        $result = StudentActionService::submitAssignment($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
