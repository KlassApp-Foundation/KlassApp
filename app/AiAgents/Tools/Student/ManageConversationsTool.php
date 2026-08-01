<?php

namespace App\AiAgents\Tools\Student;

use App\AiAgents\Concerns\AuthorizesStudentToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\StudentActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ManageConversationsTool implements Tool
{
    use AuthorizesStudentToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Manage the signed-in student\'s conversations. action=list|show|create|send. show/send require membership; create needs recipient_user_id (same school classmate/teacher/admin) and body.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->required(),
            'conversation_id' => $schema->integer()->nullable(),
            'body' => $schema->string()->nullable(),
            'recipient_user_id' => $schema->integer()->nullable(),
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
            'conversation_id' => $request->get('conversation_id'),
            'body' => $request->get('body'),
            'recipient_user_id' => $request->get('recipient_user_id'),
        ];

        if (in_array($args['action'], ['create', 'send'], true)) {
            $confirm = $this->confirmOrExecute(
                'toolStudentManageConversations',
                $args,
                fn () => "Conversation {$args['action']}"
            );
            if ($confirm !== null) {
                return $confirm;
            }
        }

        $result = StudentActionService::manageConversations($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
