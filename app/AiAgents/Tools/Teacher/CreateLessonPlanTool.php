<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\TeacherActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateLessonPlanTool implements Tool
{
    use AuthorizesTeacherToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Create a lesson plan for a class/subject you are Teacherlink-linked to.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'standardLink_id' => $schema->integer()->required(),
            'subject_id' => $schema->integer()->required(),
            'title' => $schema->string()->required(),
            'description' => $schema->string()->nullable(),
            'unit_no' => $schema->string()->nullable(),
            'unit_name' => $schema->string()->nullable(),
            'duration' => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = ['standardLink_id' => (int) $request->get('standardLink_id'), 'subject_id' => (int) $request->get('subject_id'), 'title' => $request->get('title'), 'description' => $request->get('description'), 'unit_no' => $request->get('unit_no'), 'unit_name' => $request->get('unit_name'), 'duration' => $request->get('duration')];

        $confirm = $this->confirmOrExecute('toolTeacherCreateLessonPlan', $args, fn () => "Create lesson plan: {$args['title']}");
        if ($confirm !== null) {
            return $confirm;
        }
        $result = TeacherActionService::createLessonPlan($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
