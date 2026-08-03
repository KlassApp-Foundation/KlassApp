<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\Services\Toshi\SchoolAcademicsOpsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListStudentHomeworkTool implements Tool
{
    use AuthorizesTeacherToshiAction;

    public function description(): Stringable|string
    {
        return 'List student homework submissions for a homework id. Requires studentHomework-review.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'homework_id' => $schema->integer()->required(),
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

        $result = SchoolAcademicsOpsActionService::listStudentHomework($user, [
            'homework_id' => $request->get('homework_id'),
            'limit' => $request->get('limit') ?? 20,
        ]);

        return ($result['success'] ? '' : '❌ ').$result['message'];
    }
}
