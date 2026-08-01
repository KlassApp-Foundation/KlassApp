<?php

namespace App\AiAgents\Tools\Receptionist;

use App\AiAgents\Concerns\AuthorizesReceptionistToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\ReceptionistActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTaskTool implements Tool
{
    use AuthorizesReceptionistToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Create a personal/school task on the receptionist task list.';
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
        $error = $this->authorizeReceptionistOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = [
            'title' => $request->get('title'),
            'to_do_list' => $request->get('to_do_list'),
            'task_date' => $request->get('task_date'),
        ];

        $confirm = $this->confirmOrExecute(
            'toolReceptionistCreateTask',
            $args,
            fn () => "Create task: {$args['title']}"
        );
        if ($confirm !== null) {
            return $confirm;
        }

        $result = ReceptionistActionService::createTask($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
