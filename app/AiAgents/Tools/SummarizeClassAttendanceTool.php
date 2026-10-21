<?php

namespace App\AiAgents\Tools;

use App\AiAgents\Concerns\AuthorizesToshiAction;
use App\Models\Attendance;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\Toshi\SchoolStreamLookup;
use App\Services\ToshiActionService;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Per-student present / absent / late counts for one class over a date range.
 *
 * attendances.status is a boolean (1 present, 0 absent). A row counts as late
 * when status is present and remarks is "late" — that is how the teacher
 * attendance writer stores the word.
 */
class SummarizeClassAttendanceTool implements Tool
{
    use AuthorizesToshiAction;

    private const MAX_STUDENTS = 40;

    private const MAX_DAYS = 92;

    public function description(): string
    {
        return 'Summarise attendance for one class over a date range. Returns each active student\'s present, absent, and late counts. Required: class, from (Y-m-d), to (Y-m-d). Optional: stream. Read only. Range is capped at 92 days and 40 students.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'class' => $schema->string()->description('Class or section name, for example P.1')->required(),
            'from' => $schema->string()->description('Start date Y-m-d')->required(),
            'to' => $schema->string()->description('End date Y-m-d')->required(),
            'stream' => $schema->string()->description('Optional stream name')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user() ?? request()->user();
        $error = $this->authorizeOrMessage($user);
        if ($error) {
            return $error;
        }

        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return '❌ You are not assigned to a school.';
        }

        $class = trim((string) $request->get('class', ''));
        if ($class === '') {
            return '❌ A class name is required.';
        }

        try {
            $from = Carbon::parse((string) $request->get('from'))->startOfDay();
            $to = Carbon::parse((string) $request->get('to'))->startOfDay();
        } catch (\Throwable) {
            return '❌ Use dates in Y-m-d format.';
        }

        if ($to->lt($from)) {
            return '❌ The end date is before the start date.';
        }

        if ($from->diffInDays($to) > self::MAX_DAYS) {
            return '❌ The date range is longer than '.self::MAX_DAYS.' days. Ask for a shorter range.';
        }

        $links = SchoolStreamLookup::currentLinks((int) $schoolId, $class, $this->text($request->get('stream')));
        if ($links->isEmpty()) {
            return 'No class matched that filter in your school.';
        }

        $linkIds = $links->pluck('id')->map(fn ($id) => (int) $id)->all();
        $activeStudentIds = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->pluck('id');

        $enrolments = StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->whereIn('standardLink_id', $linkIds)
            ->whereIn('user_id', $activeStudentIds)
            ->whereNull('deleted_at')
            ->whereIn('academic_year_id', function ($q) use ($schoolId) {
                $q->select('id')->from('academic_years')
                    ->where('school_id', $schoolId)
                    ->where('status', 1);
            })
            ->orderByDesc('id')
            ->get()
            ->unique('user_id');

        if ($enrolments->isEmpty()) {
            return 'No active students in that class.';
        }

        $studentIds = $enrolments->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $students = User::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $studentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $counts = [];
        foreach ($students as $student) {
            $counts[(int) $student->id] = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
        }

        $rows = Attendance::query()
            ->where('school_id', $schoolId)
            ->whereIn('standardLink_id', $linkIds)
            ->whereIn('user_id', array_keys($counts))
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get(['user_id', 'status', 'remarks']);

        foreach ($rows as $row) {
            $userId = (int) $row->user_id;
            if (! isset($counts[$userId])) {
                continue;
            }
            $bucket = $this->bucket($row->status, $row->remarks);
            $counts[$userId][$bucket]++;
        }

        $ordered = $students->sortBy(fn (User $student) => mb_strtolower((string) ($student->displayName ?: $student->name)))->values();
        $total = $ordered->count();
        $shown = $ordered->take(self::MAX_STUDENTS);
        $lines = $shown->map(function (User $student) use ($counts) {
            $tally = $counts[(int) $student->id];
            $name = $student->displayName ?: $student->name;
            $line = '• '.$name.' — present '.$tally['present'].', absent '.$tally['absent'].', late '.$tally['late'];
            if ($tally['excused'] > 0) {
                $line .= ', excused '.$tally['excused'];
            }

            return $line;
        })->implode("\n");

        $classLabel = $links->map(fn ($link) => SchoolStreamLookup::label($link))->unique()->implode(', ');
        $header = '**Attendance for '.$classLabel.' ('.$from->toDateString().' to '.$to->toDateString().', '.$total.' students):**';
        if ($total > self::MAX_STUDENTS) {
            $header .= ' Showing the first '.self::MAX_STUDENTS.'.';
        }

        return $header."\n".$lines;
    }

    private function bucket(mixed $status, mixed $remarks): string
    {
        $remark = strtolower(trim((string) $remarks));
        if ((int) $status === 0) {
            return 'absent';
        }
        if ($remark === 'late') {
            return 'late';
        }
        if ($remark === 'excused') {
            return 'excused';
        }

        return 'present';
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
