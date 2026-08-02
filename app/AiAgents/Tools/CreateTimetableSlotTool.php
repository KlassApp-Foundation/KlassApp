<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\SchoolAcademicsOpsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTimetableSlotTool implements Tool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Create a timetable slot (section/subject/teacher/day/time). No destroy.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'section_id' => $schema->integer()->required(),
            'subject_id' => $schema->integer()->required(),
            'teacher_id' => $schema->integer()->required(),
            'day_of_week' => $schema->integer()->description('0=Sun … 6=Sat')->required(),
            'start_time' => $schema->string()->description('H:i')->required(),
            'end_time' => $schema->string()->description('H:i')->required(),
            'room' => $schema->string()->nullable(),
            'academic_term_id' => $schema->integer()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = [
            'section_id' => $request->get('section_id'),
            'subject_id' => $request->get('subject_id'),
            'teacher_id' => $request->get('teacher_id'),
            'day_of_week' => $request->get('day_of_week'),
            'start_time' => $request->get('start_time'),
            'end_time' => $request->get('end_time'),
            'room' => $request->get('room'),
            'academic_term_id' => $request->get('academic_term_id'),
        ];

        $confirm = $this->confirmOrExecute('toolCreateTimetableSlot', $args,
            fn () => "Create timetable slot: day=".$args['day_of_week']." ".$args['start_time']."-".$args['end_time']);
        if ($confirm !== null) {
            return $confirm;
        }

        $result = SchoolAcademicsOpsActionService::createTimetableSlot($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
