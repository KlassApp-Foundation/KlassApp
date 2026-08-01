<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\TeacherActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateClassWallPostTool implements Tool
{
    use AuthorizesTeacherToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Publish a class wall post visible to students/parents.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'description' => $schema->string()->required(),
            'visibility' => $schema->string()->nullable(),
            'standardLink_id' => $schema->integer()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = ['description' => $request->get('description'), 'visibility' => $request->get('visibility'), 'standardLink_id' => $request->get('standardLink_id')];

        $confirm = $this->confirmOrExecute('toolTeacherCreateClassWallPost', $args, fn () => "Publish class wall post");
        if ($confirm !== null) {
            return $confirm;
        }
        $result = TeacherActionService::createClassWallPost($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
