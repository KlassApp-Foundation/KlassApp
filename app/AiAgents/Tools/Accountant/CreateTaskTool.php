<?php

namespace App\AiAgents\Tools\Accountant;

use App\AiAgents\Concerns\AuthorizesAccountantToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\AccountantActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTaskTool implements Tool
{
    use AuthorizesAccountantToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Create a personal/school task on the accountant task list.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'to_do_list' => $schema->string()->nullable(),
            'task_date' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeAccountantOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = [
            'title' => $request->get('title'),
            'to_do_list' => $request->get('to_do_list'),
            'task_date' => $request->get('task_date'),
        ];

        $confirm = $this->confirmOrExecute(
            'toolAccountantCreateTask',
            $args,
            fn () => "Create task: {$args['title']}"
        );
        if ($confirm !== null) {
            return $confirm;
        }

        $result = AccountantActionService::createTask($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
