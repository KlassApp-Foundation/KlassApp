<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\Services\Toshi\SchoolAcademicsOpsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ShowStudentHomeworkTool implements Tool
{
    use AuthorizesTeacherToshiAction;

    public function description(): Stringable|string
    {
        return 'Show one student homework submission. Requires studentHomework-review.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'student_homework_id' => $schema->integer()->required(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = SchoolAcademicsOpsActionService::showStudentHomework($user, [
            'student_homework_id' => $request->get('student_homework_id'),
        ]);

        return ($result['success'] ? '' : '❌ ').$result['message'];
    }
}
