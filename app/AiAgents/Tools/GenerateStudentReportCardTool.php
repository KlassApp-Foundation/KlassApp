<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\Services\StudentReportCardService;
use App\Services\ToshiActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * School Admin / Deputy — generate a per-student report-card PDF (school-wide).
 */
class GenerateStudentReportCardTool implements Tool
{
    use AuthorizesToshiAction;

    public function description(): Stringable|string
    {
        return 'Generate a per-student academic report card PDF for a student and exam. '
            .'School-wide (any student in the school). Returns a download URL. '
            .'Does not batch, email, or store a report_cards row.';
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
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $user = ToshiActionService::getEffectiveUser($user);
        $result = app(StudentReportCardService::class)->generateForSchoolStaff(
            $user,
            (int) $request->get('student_id'),
            (int) $request->get('exam_id')
        );

        return ($result['success'] ? '' : '❌ ').$result['message'];
    }
}
