<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\TeacherActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateAssignmentTool implements Tool
{
    use AuthorizesTeacherToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Create an assignment for a class/subject you teach.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'standardLink_id' => $schema->integer()->required(),
            'subject_id' => $schema->integer()->required(),
            'title' => $schema->string()->required(),
            'description' => $schema->string()->nullable(),
            'assigned_date' => $schema->string()->required(),
            'submission_date' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = ['standardLink_id' => (int) $request->get('standardLink_id'), 'subject_id' => (int) $request->get('subject_id'), 'title' => $request->get('title'), 'description' => $request->get('description'), 'assigned_date' => $request->get('assigned_date'), 'submission_date' => $request->get('submission_date')];

        $confirm = $this->confirmOrExecute('toolTeacherCreateAssignment', $args, fn () => "Create assignment: {$args['title']}");
        if ($confirm !== null) {
            return $confirm;
        }
        $result = TeacherActionService::createAssignment($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
