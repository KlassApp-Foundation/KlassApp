<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\SchoolAcademicsOpsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ApproveHomeworkTool implements Tool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Approve a pending homework via homework-manage Gate (admin oversight).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'homework_id' => $schema->integer()->required(),
            'comments' => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = [
            'homework_id' => $request->get('homework_id'),
            'comments' => $request->get('comments'),
        ];

        $confirm = $this->confirmOrExecute('toolApproveHomework', $args,
            fn () => "Approve homework #".$args['homework_id']);
        if ($confirm !== null) {
            return $confirm;
        }

        $result = SchoolAcademicsOpsActionService::approveHomework($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
