<?php

namespace App\AiAgents\Tools\Student;

use App\AiAgents\Concerns\AuthorizesStudentToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\StudentActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SubmitHomeworkTool implements Tool
{
    use AuthorizesStudentToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Submit homework (or a reply_comment on an existing submission) for the signed-in student only. Provide homework_id; optional note/reply_comment. Do not pass student_id.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'homework_id' => $schema->integer()->required(),
            'note' => $schema->string()->nullable(),
            'reply_comment' => $schema->string()->nullable(),
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
            'homework_id' => $request->get('homework_id'),
            'note' => $request->get('note'),
            'reply_comment' => $request->get('reply_comment'),
        ];

        $confirm = $this->confirmOrExecute(
            'toolStudentSubmitHomework',
            $args,
            fn () => "Submit homework #{$args['homework_id']}"
        );
        if ($confirm !== null) {
            return $confirm;
        }

        $result = StudentActionService::submitHomework($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
