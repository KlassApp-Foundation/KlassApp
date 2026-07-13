<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class RecordBulkAttendanceTool implements Tool
{
    public function description(): string
    {
        return 'Record attendance for multiple students at once. Provide a JSON array of student records, each with student identifier (name, email, or ID), date, and status.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'students' => $schema->array()
                ->items($schema->object([
                    'student' => $schema->string()->description('Student name, email, or ID'),
                    'date' => $schema->string()->description('Date in Y-m-d format'),
                    'status' => $schema->string()->enum(['present', 'absent', 'late', 'excused'])->description('Attendance status'),
                ]))
                ->description('Array of student attendance records'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $result = \App\Services\ToshiActionService::recordBulkAttendance($user, [
            'students' => $request->get('students', []),
        ]);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }
}
