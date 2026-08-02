<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\SchoolAcademicsOpsActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateTimetableSlotTool implements Tool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Update an existing timetable slot by id. School-scoped; no destroy.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slot_id' => $schema->integer()->required(),
            'section_id' => $schema->integer()->nullable(),
            'subject_id' => $schema->integer()->nullable(),
            'teacher_id' => $schema->integer()->nullable(),
            'day_of_week' => $schema->integer()->nullable(),
            'start_time' => $schema->string()->nullable(),
            'end_time' => $schema->string()->nullable(),
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
            'slot_id' => $request->get('slot_id'),
            'section_id' => $request->get('section_id'),
            'subject_id' => $request->get('subject_id'),
            'teacher_id' => $request->get('teacher_id'),
            'day_of_week' => $request->get('day_of_week'),
            'start_time' => $request->get('start_time'),
            'end_time' => $request->get('end_time'),
            'room' => $request->get('room'),
            'academic_term_id' => $request->get('academic_term_id'),
        ];

        $confirm = $this->confirmOrExecute('toolUpdateTimetableSlot', $args,
            fn () => "Update timetable slot #".$args['slot_id']);
        if ($confirm !== null) {
            return $confirm;
        }

        $result = SchoolAcademicsOpsActionService::updateTimetableSlot($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
