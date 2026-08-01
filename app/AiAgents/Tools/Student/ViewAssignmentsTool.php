<?php

namespace App\AiAgents\Tools\Student;

use App\AiAgents\Concerns\AuthorizesStudentToshiAction;
use App\Services\Toshi\StudentActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ViewAssignmentsTool implements Tool
{
    use AuthorizesStudentToshiAction;

    public function description(): Stringable|string
    {
        return 'List assignments for the signed-in student class with own submission status.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeStudentOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = StudentActionService::viewAssignments($user);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
