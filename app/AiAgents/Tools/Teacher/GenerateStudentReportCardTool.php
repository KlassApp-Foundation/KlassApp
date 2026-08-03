<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\Services\StudentReportCardService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Teacher — generate a per-student report-card PDF for an assigned-class student only.
 */
class GenerateStudentReportCardTool implements Tool
{
    use AuthorizesTeacherToshiAction;

    public function description(): Stringable|string
    {
        return 'Generate a per-student academic report card PDF for a student in your assigned class '
            .'(class teacher or Teacherlink). Returns a download URL. Not school-wide.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'student_id' => $schema->integer()->required()->description('Student user id'),
            'exam_id' => $schema->integer()->required()->description('Exam id (anchors term/section)'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $result = app(StudentReportCardService::class)->generateForTeacher(
            $user,
            (int) $request->get('student_id'),
            (int) $request->get('exam_id')
        );

        return ($result['success'] ? '' : '❌ ').$result['message'];
    }
}
