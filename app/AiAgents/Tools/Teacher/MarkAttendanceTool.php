<?php

namespace App\AiAgents\Tools\Teacher;

use App\AiAgents\Concerns\AuthorizesTeacherToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\Services\Toshi\TeacherActionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class MarkAttendanceTool implements Tool
{
    use AuthorizesTeacherToshiAction;
    use ConfirmsBeforeWrite;

    public function description(): Stringable|string
    {
        return 'Record attendance for one student (teacher). Provide student identifier, date (Y-m-d), and status.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'student' => $schema->string()->required(),
            'date' => $schema->string()->required(),
            'status' => $schema->string()->enum(['present', 'absent', 'late', 'excused']),
            'standardLink_id' => $schema->integer()->nullable(),
            'session' => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeTeacherOrMessage($user);
        if ($error) {
            return $error;
        }

        $args = ['student' => $request->get('student'), 'date' => $request->get('date'), 'status' => $request->get('status', 'present'), 'standardLink_id' => $request->get('standardLink_id'), 'session' => $request->get('session')];

        $confirm = $this->confirmOrExecute('toolTeacherMarkAttendance', $args, fn () => "Record attendance: {$args['student']} on {$args['date']} as {$args['status']}");
        if ($confirm !== null) {
            return $confirm;
        }
        $result = TeacherActionService::markAttendance($user, $args);

        return ($result['success'] ? '✅ ' : '❌ ').$result['message'];
    }
}
