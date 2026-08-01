<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\TeacherActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ApplyLeaveTool implements Tool
{
    use AuthorizesTeacherToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Apply for teacher leave (pending approval).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_date' => $schema->string()->required(),
            'to_date' => $schema->string()->required(),
            'reason_id' => $schema->integer()->required(),
            'leave_type_id' => $schema->integer()->required(),
            'session' => $schema->string()->nullable(),
            'remarks' => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = ['from_date' => $request->get('from_date'), 'to_date' => $request->get('to_date'), 'reason_id' => (int) $request->get('reason_id'), 'leave_type_id' => (int) $request->get('leave_type_id'), 'session' => $request->get('session'), 'remarks' => $request->get('remarks')];

        $confirm = $this->confirmOrExecute('toolTeacherApplyLeave', $args, fn () => "Apply leave {$args['from_date']} to {$args['to_date']}");
        if ($confirm !== null) {
            return $confirm;
        }
        $result = TeacherActionService::applyLeave($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
