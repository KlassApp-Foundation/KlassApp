<?php

namespace App\AiAgents\Tools\Student;

use App\AiAgents\Concerns\AuthorizesStudentToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\StudentActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ManageTasksTool implements Tool
{
    use AuthorizesStudentToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Manage the signed-in student\'s personal tasks. action=list|create|show|update|complete. Creates/updates only tasks owned by the student. task_id required for show/update/complete.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->required(),
            'title' => $schema->string()->nullable(),
            'to_do_list' => $schema->string()->nullable(),
            'task_date' => $schema->string()->nullable(),
            'task_id' => $schema->integer()->nullable(),
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
            'action' => $request->get('action') ?? 'list',
            'title' => $request->get('title'),
            'to_do_list' => $request->get('to_do_list'),
            'task_date' => $request->get('task_date'),
            'task_id' => $request->get('task_id'),
        ];

        // Reads (list/show) skip Tier-2; writes require confirm.
        if (in_array($args['action'], ['create', 'update', 'complete'], true)) {
            $confirm = $this->confirmOrExecute(
                'toolStudentManageTasks',
                $args,
                fn () => "Task {$args['action']}: ".($args['title'] ?? ('#'.$args['task_id']))
            );
            if ($confirm !== null) {
                return $confirm;
            }
        }

        $result = StudentActionService::manageTasks($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
