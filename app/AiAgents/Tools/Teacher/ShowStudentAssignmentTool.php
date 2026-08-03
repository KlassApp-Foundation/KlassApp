<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\Services\Toshi\TeacherActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ShowStudentAssignmentTool implements Tool
{
    use AuthorizesTeacherToshiAction;

    public function description(): Stringable|string
    {
        return 'Show one student assignment submission. Requires studentAssignment-review.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'student_assignment_id' => $schema->integer()->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = TeacherActionService::showStudentAssignment($user, [
            'student_assignment_id' => $request->get('student_assignment_id'),
        ]);

        return ($result['success'] ? '' : '❌ ').$result['message'];
    }
}
