<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\Attendance;

class RecordAttendanceTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;

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
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $args = [
            'student' => $request->get('student'),
            'date' => $request->get('date'),
            'status' => $request->get('status', 'present'),
            'session' => $request->get('session'),
        ];

        $confirm = $this->confirmOrExecute('toolRecordAttendance', $args,
            fn() => "Record attendance: {$args['student']} on {$args['date']} as {$args['status']}");
        if ($confirm !== null) return $confirm;

        $user = \App\Services\ToshiActionService::getEffectiveUser($user);
        $result = \App\Services\ToshiActionService::recordAttendance($user, $args);
        return $result['success'] ? '✅ ' . $result['message'] : '❌ ' . $result['message'];
    }

    public function verify(Request $request): array
    {
        $user = auth()->user() ?? request()->user();
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return ['verified' => false, 'message' => 'No school assigned for verification.'];
        }

        $studentIdentifier = trim($request->get('student', ''));
        $date = trim($request->get('date', now()->toDateString()));
        $status = strtolower(trim($request->get('status', 'present')));
        // Map to integer: attendances.status is tinyint(1), not a string
        $statusMap = ['present' => 1, 'absent' => 0, 'late' => 1, 'half-day' => 1, 'holiday' => 1];
        $statusInt = $statusMap[$status] ?? 1;

        $student = \App\Models\User::where('school_id', $schoolId)
            ->where(function ($q) use ($studentIdentifier) {
                $q->where('email', $studentIdentifier)
                  ->orWhere('name', 'LIKE', '%' . $studentIdentifier . '%');
            })
            ->where('usergroup_id', 6)
            ->first();
        if (!$student) {
            return ['verified' => false, 'message' => 'Student not found during verification.'];
        }

        try {
            $parsedDate = \Carbon\Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            return ['verified' => false, 'message' => 'Invalid date format during verification.'];
        }

        $exists = Attendance::where('school_id', $schoolId)
            ->where('user_id', $student->id)
            ->whereDate('date', $parsedDate)
            ->where('status', $statusInt)
            ->exists();

        return [
            'verified' => $exists,
            'message' => $exists
                ? 'Attendance record confirmed in database.'
                : 'Attendance record was not found after writing — it may not have persisted.',
        ];
    }
}
