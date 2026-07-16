<?php

namespace App\AiAgents\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Concerns\HasPreExecutionGuards;
use App\AiAgents\Concerns\VerifiableTool;
use App\Models\Attendance;
use App\Models\User;
use App\Services\EntityResolver;
use App\Services\ToshiActionService;
use Illuminate\Support\Facades\DB;

class RecordBulkAttendanceTool implements Tool, VerifiableTool
{
    use AuthorizesToshiAction;
    use ConfirmsBeforeWrite;
    use HasPreExecutionGuards;

    private array $resolvedStudents = [];
    private array $unresolvedNames = [];
    private array $rawStudents = [];

    public function description(): string
    {
        return 'Record attendance for multiple students at once. Provide a JSON array of student records, each with student name, date, and status. Always use student names, not IDs.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'students' => $schema->array()
                ->items($schema->object([
                    'student' => $schema->string()->description('Student full name'),
                    'date' => $schema->string()->description('Date in Y-m-d format'),
                    'status' => $schema->string()->enum(['present', 'absent', 'late', 'excused'])->description('Attendance status'),
                ]))
                ->description('Array of student attendance records'),
        ];
    }

    // ── Guards ──

    protected function guardStudentsNotEmpty(): ?string
    {
        $students = $this->getRawStudents();
        if (empty($students)) {
            return 'needs_fallback_split';
        }
        return null;
    }

    protected function guardStudentNamesAreComplete(): ?string
    {
        $students = $this->getRawStudents();
        foreach ($students as $i => $entry) {
            $name = trim($entry['student'] ?? '');
            if (str_word_count($name) < 2 && strlen($name) > 0) {
                return "❌ \"{$name}\" doesn't look like a full name. Please provide the student's full name (e.g. \"Bob Student\").";
            }
            if (empty($name)) {
                return "❌ Student record #" . ($i + 1) . " is missing a name.";
            }
        }
        return null;
    }

    protected function guardValidStatuses(): ?string
    {
        $valid = ['present', 'absent', 'late', 'excused'];
        foreach ($this->getRawStudents() as $i => $entry) {
            $status = strtolower(trim($entry['status'] ?? 'present'));
            if (!in_array($status, $valid)) {
                return "❌ Record #" . ($i + 1) . " (" . ($entry['student'] ?? '?') . "): status must be one of: " . implode(', ', $valid) . ".";
            }
        }
        return null;
    }

    protected function guardNoDuplicateNames(): ?string
    {
        $names = [];
        foreach ($this->getRawStudents() as $entry) {
            $n = strtolower(trim($entry['student'] ?? ''));
            if (in_array($n, $names)) {
                return "❌ Duplicate student: \"{$entry['student']}\" appears more than once in the same batch.";
            }
            $names[] = $n;
        }
        return null;
    }

    private function getRawStudents(): array
    {
        return $this->rawStudents ?? [];
    }

    // ── Handle ──

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) return $error;

        $this->rawStudents = $request->get('students', []);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);

        // Run guards — special case: empty students triggers fallback split
        $guardResult = $this->runGuards();
        if ($guardResult === 'needs_fallback_split') {
            return $this->fallbackSplit($request, $user, $schoolId);
        }
        if ($guardResult !== null) {
            return $guardResult;
        }

        // Resolve each student name to a model
        $this->resolvedStudents = [];
        $this->unresolvedNames = [];
        foreach ($this->rawStudents as $entry) {
            $result = EntityResolver::resolveStudent($schoolId, trim($entry['student']));
            if ($result['state'] === 'resolved') {
                $this->resolvedStudents[] = [
                    'model' => $result['model'],
                    'status' => strtolower(trim($entry['status'] ?? 'present')),
                    'date' => $entry['date'] ?? now()->toDateString(),
                ];
            } else {
                $this->unresolvedNames[] = $entry['student'];
            }
        }

        if (!empty($this->unresolvedNames)) {
            $list = implode(', ', array_map(fn($n) => "\"{$n}\"", $this->unresolvedNames));
            return "❌ Could not find student(s): {$list}. Please check the spelling and try again.";
        }

        $count = count($this->resolvedStudents);
        $previewLines = [];
        foreach ($this->resolvedStudents as $rs) {
            $previewLines[] = $rs['model']->name . ' → ' . $rs['status'];
        }
        $previewStr = implode('; ', $previewLines);

        $args = ['students' => $this->resolvedStudents];
        $confirm = $this->confirmOrExecute('toolRecordBulkAttendance', $args,
            fn() => "Record attendance for {$count} student(s): {$previewStr}");
        if ($confirm !== null) return $confirm;

        // Second pass — write inside a transaction
        DB::beginTransaction();
        try {
            $written = 0;
            foreach ($this->resolvedStudents as $rs) {
                $studentAcademic = \App\Models\StudentAcademic::where('school_id', $schoolId)
                    ->where('user_id', $rs['model']->id)
                    ->first();

                Attendance::create([
                    'school_id'        => $schoolId,
                    'user_id'          => $rs['model']->id,
                    'date'             => $rs['date'],
                    'status'           => 1,
                    'session'          => 'forenoon',
                    'academic_year_id' => $studentAcademic?->academic_year_id,
                    'standardLink_id'  => $studentAcademic?->standardLink_id,
                    'recorded_by'      => $user->id,
                ]);
                $written++;
            }

            if ($written !== count($this->resolvedStudents)) {
                throw new \RuntimeException("Wrote {$written} rows but expected " . count($this->resolvedStudents));
            }

            DB::commit();
            return "✅ Attendance recorded for {$written} student(s).";
        } catch (\Exception $e) {
            DB::rollBack();
            return "❌ Failed to record attendance: " . $e->getMessage();
        }
    }

    /**
     * Fallback: if the LLM sent an empty students array, re-parse the
     * original user message for "Name, status" / "Name as status" patterns
     * and route each through the single RecordAttendanceTool.
     */
    private function fallbackSplit(Request $request, $user, int $schoolId): string
    {
        // This path isn't reachable from the LLM's structured output —
        // it's reached when the LLM sends `"students": []`. In that case,
        // return a clarifying message instead of silently dropping.
        return "❌ I couldn't identify any students in your request. Please specify students clearly — for example: \"Bob Student present, Charlie Student absent\".";
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
            $model = $item['model'] ?? null;
            if (!$model) {
                $missing++;
                continue;
            }
            $exists = Attendance::where('school_id', $schoolId)
                ->where('user_id', $model->id)
                ->whereDate('date', $item['date'] ?? now()->toDateString())
                ->exists();
            if (!$exists) {
                $missing++;
            }
        }

        return [
            'verified' => $missing === 0,
            'message' => $missing === 0
                ? 'All attendance records confirmed in database.'
                : "{$missing} attendance record(s) not found after writing.",
        ];
    }
}
