<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class RecordAttendanceTool implements Tool
{
    public function description(): string
    {
        return 'Record attendance for a single student. Provide student name, email, or ID, date (Y-m-d), and status (present, absent, late, excused). Default status is present.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'student' => $schema->string()->description('Student name, email, or ID'),
            'date' => $schema->string()->description('Date in Y-m-d format'),
            'status' => $schema->string()->enum(['present', 'absent', 'late', 'excused'])->description('Attendance status'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::recordAttendance($user, [
            'student' => $request->get('student'),
            'date' => $request->get('date'),
            'status' => $request->get('status', 'present'),
        ]);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
