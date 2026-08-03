<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\TeacherActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CancelMyLeaveTool implements Tool
{
    use AuthorizesTeacherToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Cancel a pending leave application owned by the authenticated teacher. Requires teacher-leave.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'leave_id' => $schema->integer()->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = ['leave_id' => $request->get('leave_id')];

        $confirm = $this->confirmOrExecute('toolTeacherCancelMyLeave', $args,
            fn () => 'Cancel leave application #'.$args['leave_id']);
        if ($confirm !== null) {
            return $confirm;
        }

        $result = TeacherActionService::cancelMyLeave($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
