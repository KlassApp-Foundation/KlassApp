<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\Services\Toshi\TeacherActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListStudentAssignmentsTool implements Tool
{
    use AuthorizesTeacherToshiAction;

    public function description(): Stringable|string
    {
        return 'List student assignment submissions for an assignment id. Requires studentAssignment-review.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'assignment_id' => $schema->integer()->required(),
            'status' => $schema->string()->nullable()->description('Optional filter: submitted or completed'),
            'limit' => $schema->integer()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = TeacherActionService::listStudentAssignments($user, [
            'assignment_id' => $request->get('assignment_id'),
            'status' => $request->get('status'),
            'limit' => $request->get('limit') ?? 20,
        ]);

        return ($result['success'] ? '' : '❌ ').$result['message'];
    }
}
