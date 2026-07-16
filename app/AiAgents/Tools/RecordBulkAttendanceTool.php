<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\Attendance;

class RecordBulkAttendanceTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

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
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $args = [
            'students' => $request->get('students', []),
        ];

        $count = is_array($args['students']) ? count($args['students']) : 0;
        $confirm = $this->confirmOrExecute('toolRecordBulkAttendance', $args,
            fn() => "Record bulk attendance for {$count} student(s)");
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::recordBulkAttendance($user, $args);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $students = $request->get('students', []);
        if (empty($students)) {
            return ['verified' => false, 'message' => 'No student records to verify.'];
        }

        $missing = 0;
        foreach ($students as $item) {
            $studentIdentifier = trim($item['student'] ?? '');
            $date = trim($item['date'] ?? $request->get('date', now()->toDateString()));
            $status = strtolower(trim($item['status'] ?? 'present'));

            $student = \App\Models\User::where('school_id', $schoolId)
                ->where('usergroup_id', 6)
                ->where(function ($q) use ($studentIdentifier) {
                    $q->where('email', $studentIdentifier)
                      ->orWhere('name', 'LIKE', '%' . $studentIdentifier . '%');
                })
                ->first();

            if (!$student) {
                $missing++;
                continue;
            }

            try {
                $parsedDate = \Carbon\Carbon::parse($date)->toDateString();
            } catch (\Exception $e) {
                $missing++;
                continue;
            }

            $exists = Attendance::where('school_id', $schoolId)
                ->where('user_id', $student->id)
                ->whereDate('date', $parsedDate)
                ->where('status', $status)
                ->exists();

            if (!$exists) {
                $missing++;
            }
        }

        $verified = $missing === 0;
        return [
            'verified' => $verified,
            'message' => $verified
                ? 'All attendance records confirmed in database.'
                : "{$missing} attendance record(s) not found after writing.",
        ];
    }
}
