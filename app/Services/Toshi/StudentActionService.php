<?php

namespace App\Services\Toshi;

use App\Models\Academics\Marks;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\BookLending;
use App\Models\Events;
use App\Models\Homework;
use App\Models\NoticeBoard;
use App\Models\Post;
use App\Models\StudentAssignment;
use App\Models\StudentHomework;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Student-scoped Toshi reads/writes (ug6) — hard self-scope.
 *
 * Identity is always the authenticated User passed in; LLM-supplied student_id /
 * user_id arguments are ignored. Resource IDs are re-checked against that actor.
 *
 * Writes (Tier-2 at tool layer): submitAssignment, submitHomework, manageTasks, manageConversations
 * Reads: viewDashboard, viewMarks, viewAttendance, viewLibraryActivity, viewEvents,
 *        viewNotices, viewAssignments, viewHomework, viewClassWall
 */
class StudentActionService
{
    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public static function result(bool $success, string $message, array $data = []): array
    {
        $out = [
            'success' => $success,
            'message' => $message,
        ];
        if ($data !== []) {
            $out['data'] = $data;
        }

        return $out;
    }

    /**
     * Resolve the acting student from auth context only.
     * Deliberately ignores any LLM-supplied student_id / user_id.
     */
    public static function resolveActor(User $user): User
    {
        return $user;
    }

    private static function academicYear(int $schoolId): ?\App\Models\AcademicYear
    {
        // Avoid SiteHelper::getAcademicYear() Cache::remember pollution across tests/requests.
        return \App\Models\AcademicYear::where('school_id', $schoolId)
            ->where(function ($q) {
                $q->where('description', 'Current Academic Year')
                    ->orWhere('status', 1);
            })
            ->orderByDesc('id')
            ->first();
    }

    public static function standardLinkId(User $student): ?int
    {
        $link = $student->studentAcademicLatest?->standardLink_id;
        if ($link !== null) {
            return (int) $link;
        }

        $academicYear = self::academicYear((int) $student->school_id);
        if (! $academicYear) {
            return null;
        }

        $row = \App\Models\StudentAcademic::where('user_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->orderByDesc('id')
            ->first();

        return $row?->standardLink_id !== null ? (int) $row->standardLink_id : null;
    }

    public static function viewDashboard(User $student): array
    {
        $student = self::resolveActor($student);
        $schoolId = (int) $student->school_id;
        $academicYear = self::academicYear($schoolId);

        $total = Attendance::where('user_id', $student->id)->count();
        $present = Attendance::where([['user_id', $student->id], ['status', 1]])->count();
        $absent = Attendance::where([['user_id', $student->id], ['status', 0]])->count();
        $presentPct = $total > 0 ? number_format(($present / $total) * 100, 0) : '0';

        $marksCount = Marks::where('student_id', $student->id)->where('school_id', $schoolId)->count();
        $lendCount = BookLending::where('user_id', $student->id)->count();
        $pendingTasks = Task::where('school_id', $schoolId)
            ->where('user_id', $student->id)
            ->where('task_status', 0)
            ->count();

        $eventsNote = '';
        if ($academicYear) {
            $events = Events::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYear->id)
                ->where('category', '!=', 'holidays')
                ->where('end_date', '>', now())
                ->count();
            $eventsNote = "\n- Upcoming events: {$events}";
        }

        $msg = "**Your dashboard**\n"
            ."- Attendance present: {$presentPct}%\n"
            ."- Present sessions: {$present}\n"
            ."- Absent sessions: {$absent}\n"
            ."- Marks recorded: {$marksCount}\n"
            ."- Library lends: {$lendCount}\n"
            ."- Pending tasks: {$pendingTasks}"
            .$eventsNote;

        return self::result(true, $msg, [
            'user_id' => $student->id,
            'present_pct' => $presentPct,
            'marks' => $marksCount,
        ]);
    }

    public static function viewMarks(User $student): array
    {
        $student = self::resolveActor($student);
        $rows = Marks::where('student_id', $student->id)
            ->where('school_id', $student->school_id)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'subject_id', 'exam_id', 'marks', 'grade']);

        if ($rows->isEmpty()) {
            return self::result(true, 'No marks recorded for you yet.', ['count' => 0]);
        }

        $lines = $rows->map(fn ($m) => "- Mark #{$m->id}: score {$m->marks}".($m->grade ? " (grade {$m->grade})" : ''))->implode("\n");

        return self::result(true, "**Your marks:**\n{$lines}", [
            'count' => $rows->count(),
            'ids' => $rows->pluck('id')->all(),
        ]);
    }

    public static function viewAttendance(User $student): array
    {
        $student = self::resolveActor($student);
        $total = Attendance::where('user_id', $student->id)->count();
        $present = Attendance::where([['user_id', $student->id], ['status', 1]])->count();
        $absent = Attendance::where([['user_id', $student->id], ['status', 0]])->count();
        $recent = Attendance::where('user_id', $student->id)
            ->orderByDesc('date')
            ->limit(10)
            ->get(['id', 'date', 'session', 'status']);

        $lines = $recent->map(function ($a) {
            $status = (int) $a->status === 1 ? 'present' : 'absent';

            return '- '.optional($a->date)->format('Y-m-d')." {$a->session}: {$status}";
        })->implode("\n");

        $msg = "**Your attendance**\n- Total sessions: {$total}\n- Present: {$present}\n- Absent: {$absent}";
        if ($lines !== '') {
            $msg .= "\n\nRecent:\n{$lines}";
        }

        return self::result(true, $msg, [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
        ]);
    }

    public static function viewLibraryActivity(User $student): array
    {
        $student = self::resolveActor($student);
        $lends = BookLending::where('user_id', $student->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        if ($lends->isEmpty()) {
            return self::result(true, 'No library activity for you.', ['count' => 0]);
        }

        $lines = $lends->map(function ($l) {
            return "- Lending #{$l->id}: book {$l->book_code_no} ({$l->status})";
        })->implode("\n");

        return self::result(true, "**Your library activity:**\n{$lines}", [
            'count' => $lends->count(),
            'ids' => $lends->pluck('id')->all(),
        ]);
    }

    public static function viewEvents(User $student): array
    {
        $student = self::resolveActor($student);
        $query = Events::where('school_id', $student->school_id)
            ->where('category', '!=', 'holidays')
            ->orderByDesc('id')
            ->limit(20);

        $academicYear = self::academicYear((int) $student->school_id);
        if ($academicYear) {
            $query->where('academic_year_id', $academicYear->id);
        }

        $events = $query->get(['id', 'title']);
        $lines = $events->map(fn ($e) => "- {$e->title} (#{$e->id})")->implode("\n");

        return self::result(true, $lines !== '' ? "**Events:**\n{$lines}" : 'No events found.');
    }

    public static function viewNotices(User $student): array
    {
        $student = self::resolveActor($student);
        $standardLinkId = self::standardLinkId($student);
        $academicYear = self::academicYear((int) $student->school_id);

        $query = NoticeBoard::where('school_id', $student->school_id)
            ->where('type', '!=', 'teacher')
            ->orderByDesc('id')
            ->limit(20);

        if ($academicYear) {
            $query->where('academic_year_id', $academicYear->id);
        }

        if ($standardLinkId) {
            $query->where(function ($q) use ($standardLinkId) {
                $q->whereNull('standardLink_id')->orWhere('standardLink_id', $standardLinkId);
            });
        }

        $notices = $query->get(['id', 'title', 'type']);
        $lines = $notices->map(fn ($n) => '- '.($n->title ?? 'Notice')." (#{$n->id})")->implode("\n");

        return self::result(true, $lines !== '' ? "**Notices:**\n{$lines}" : 'No notices found.');
    }

    public static function viewAssignments(User $student): array
    {
        $student = self::resolveActor($student);
        $standardLinkId = self::standardLinkId($student);
        if (! $standardLinkId) {
            return self::result(false, 'No class enrollment found for you.');
        }

        $academicYear = self::academicYear((int) $student->school_id);
        if (! $academicYear) {
            return self::result(false, 'No active academic year for your school.');
        }

        $assignments = Assignment::where([
            ['school_id', $student->school_id],
            ['academic_year_id', $academicYear->id],
            ['standardLink_id', $standardLinkId],
        ])->orderByDesc('id')->limit(20)->get(['id', 'title', 'submission_date', 'status']);

        $submissionIds = StudentAssignment::where('user_id', $student->id)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->pluck('status', 'assignment_id');

        $lines = $assignments->map(function ($a) use ($submissionIds) {
            $status = $submissionIds[$a->id] ?? 'not submitted';

            return "- #{$a->id} {$a->title} (due {$a->submission_date}, your status: {$status})";
        })->implode("\n");

        return self::result(true, $lines !== '' ? "**Your class assignments:**\n{$lines}" : 'No assignments for your class.', [
            'count' => $assignments->count(),
        ]);
    }

    public static function viewHomework(User $student): array
    {
        $student = self::resolveActor($student);
        $standardLinkId = self::standardLinkId($student);
        if (! $standardLinkId) {
            return self::result(false, 'No class enrollment found for you.');
        }

        $academicYear = self::academicYear((int) $student->school_id);
        if (! $academicYear) {
            return self::result(false, 'No active academic year for your school.');
        }

        $homeworks = Homework::where([
            ['school_id', $student->school_id],
            ['academic_year_id', $academicYear->id],
            ['standardLink_id', $standardLinkId],
        ])->orderByDesc('id')->limit(20)->get(['id', 'description', 'date']);

        $submissionIds = StudentHomework::where('user_id', $student->id)
            ->whereIn('homework_id', $homeworks->pluck('id'))
            ->pluck('status', 'homework_id');

        $lines = $homeworks->map(function ($h) use ($submissionIds) {
            $status = $submissionIds[$h->id] ?? 'not submitted';
            $desc = Str::limit(strip_tags((string) $h->description), 60);

            return "- #{$h->id} {$desc} (date {$h->date}, your status: {$status})";
        })->implode("\n");

        return self::result(true, $lines !== '' ? "**Your class homework:**\n{$lines}" : 'No homework for your class.', [
            'count' => $homeworks->count(),
        ]);
    }

    public static function viewClassWall(User $student): array
    {
        $student = self::resolveActor($student);
        $academicYear = self::academicYear((int) $student->school_id);
        if (! $academicYear) {
            return self::result(false, 'No active academic year for your school.');
        }

        $posts = Post::where([
            ['school_id', $student->school_id],
            ['academic_year_id', $academicYear->id],
            ['is_posted', 1],
        ])->orderByDesc('post_created_at')->limit(15)->get(['id', 'description', 'entity_id']);

        $lines = $posts->map(function ($p) {
            $desc = Str::limit(strip_tags((string) $p->description), 80);

            return "- Post #{$p->id}: {$desc}";
        })->implode("\n");

        return self::result(true, $lines !== '' ? "**Class wall (read-only):**\n{$lines}" : 'No class wall posts yet.');
    }

    /**
     * Submit assignment for the authenticated student only.
     * assignment_id must belong to the actor's class; user_id always stamped from actor.
     */
    public static function submitAssignment(User $student, array $data): array
    {
        $student = self::resolveActor($student);
        $v = Validator::make($data, [
            'assignment_id' => 'required|integer',
            'note' => 'nullable|string|max:2000',
            // Intentionally not validated: student_id / user_id — ignored if present.
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $standardLinkId = self::standardLinkId($student);
        if (! $standardLinkId) {
            return self::result(false, 'No class enrollment found for you.');
        }

        $assignment = Assignment::where('id', (int) $data['assignment_id'])
            ->where('school_id', $student->school_id)
            ->where('standardLink_id', $standardLinkId)
            ->first();

        if (! $assignment) {
            return self::result(false, 'Assignment not found for your class, or access denied.');
        }

        $existing = StudentAssignment::where('assignment_id', $assignment->id)
            ->where('user_id', $student->id)
            ->first();
        if ($existing) {
            return self::result(false, 'You already submitted this assignment (id '.$existing->id.').');
        }

        $note = $data['note'] ?? 'Submitted via Toshi';
        $row = new StudentAssignment;
        $row->assignment_id = $assignment->id;
        $row->user_id = $student->id; // never from LLM
        $row->assignment_file = [1 => 'toshi-text://'.$note];
        $row->comments = $note;
        $row->submitted_on = date('Y-m-d');
        $row->status = 'submitted';
        $row->save();

        return self::result(true, "Assignment #{$assignment->id} submitted (submission id {$row->id}).", [
            'id' => $row->id,
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
    }

    public static function submitHomework(User $student, array $data): array
    {
        $student = self::resolveActor($student);
        $v = Validator::make($data, [
            'homework_id' => 'required|integer',
            'note' => 'nullable|string|max:2000',
            'reply_comment' => 'nullable|string|max:2000',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $standardLinkId = self::standardLinkId($student);
        if (! $standardLinkId) {
            return self::result(false, 'No class enrollment found for you.');
        }

        $homework = Homework::where('id', (int) $data['homework_id'])
            ->where('school_id', $student->school_id)
            ->where('standardLink_id', $standardLinkId)
            ->first();

        if (! $homework) {
            return self::result(false, 'Homework not found for your class, or access denied.');
        }

        $existing = StudentHomework::where('homework_id', $homework->id)
            ->where('user_id', $student->id)
            ->first();

        $note = $data['note'] ?? $data['reply_comment'] ?? 'Submitted via Toshi';

        if ($existing) {
            if (! empty($data['reply_comment'])) {
                $existing->reply_comment = $data['reply_comment'];
                $existing->save();

                return self::result(true, "Homework reply saved on your submission (id {$existing->id}).", [
                    'id' => $existing->id,
                    'user_id' => $student->id,
                ]);
            }

            return self::result(false, 'You already submitted this homework (id '.$existing->id.').');
        }

        $row = new StudentHomework;
        $row->homework_id = $homework->id;
        $row->user_id = $student->id; // never from LLM
        $row->attachment = [1 => 'toshi-text://'.$note];
        $row->comments = $note;
        $row->reply_comment = $data['reply_comment'] ?? null;
        $row->submitted_on = date('Y-m-d');
        $row->status = 'unchecked';
        $row->save();

        return self::result(true, "Homework #{$homework->id} submitted (submission id {$row->id}).", [
            'id' => $row->id,
            'homework_id' => $homework->id,
            'user_id' => $student->id,
        ]);
    }

    /**
     * Manage personal tasks. Ownership: task.user_id === actor.id for mutate/list-own.
     *
     * @param  array{action?: string, title?: string, to_do_list?: string, task_date?: string, task_id?: int}  $data
     */
    public static function manageTasks(User $student, array $data): array
    {
        $student = self::resolveActor($student);
        $action = $data['action'] ?? 'list';

        if ($action === 'list') {
            $tasks = Task::where('school_id', $student->school_id)
                ->where('user_id', $student->id)
                ->orderByDesc('id')
                ->limit(20)
                ->get(['id', 'title', 'task_date', 'task_status']);

            $lines = $tasks->map(function ($t) {
                $done = (int) $t->task_status === 1 ? 'done' : 'open';

                return "- #{$t->id} {$t->title} ({$done})";
            })->implode("\n");

            return self::result(true, $lines !== '' ? "**Your tasks:**\n{$lines}" : 'You have no tasks.', [
                'count' => $tasks->count(),
                'ids' => $tasks->pluck('id')->all(),
            ]);
        }

        if ($action === 'create') {
            $v = Validator::make($data, [
                'title' => 'required|string|min:3',
                'to_do_list' => 'nullable|string',
                'task_date' => 'required|date_format:Y-m-d',
            ]);
            if ($v->fails()) {
                return self::result(false, $v->errors()->first());
            }

            $row = Task::create([
                'school_id' => $student->school_id,
                'academic_year_id' => optional(self::academicYear((int) $student->school_id))->id,
                'user_id' => $student->id,
                'title' => $data['title'],
                'type' => 'self',
                'to_do_list' => $data['to_do_list'] ?? $data['title'],
                'task_date' => $data['task_date'],
                'reminder' => 'one_day_before_the_task',
                'task_status' => 0,
                'task_flag' => 2,
            ]);

            return self::result(true, "Task **{$row->title}** created (id {$row->id}).", ['id' => $row->id]);
        }

        if (in_array($action, ['complete', 'update', 'show'], true)) {
            $taskId = (int) ($data['task_id'] ?? 0);
            if ($taskId < 1) {
                return self::result(false, 'task_id is required.');
            }

            $task = Task::where('id', $taskId)->where('school_id', $student->school_id)->first();
            if (! $task || (int) $task->user_id !== (int) $student->id) {
                return self::result(false, 'Task not found or access denied.');
            }

            if ($action === 'show') {
                return self::result(true, "Task #{$task->id}: {$task->title} (status ".((int) $task->task_status === 1 ? 'done' : 'open').').', [
                    'id' => $task->id,
                ]);
            }

            if ($action === 'complete') {
                $task->task_status = 1;
                $task->save();

                return self::result(true, "Task #{$task->id} marked complete.", ['id' => $task->id]);
            }

            // update
            if (! empty($data['title'])) {
                $task->title = $data['title'];
            }
            if (! empty($data['to_do_list'])) {
                $task->to_do_list = $data['to_do_list'];
            }
            if (! empty($data['task_date'])) {
                $task->task_date = $data['task_date'];
            }
            $task->save();

            return self::result(true, "Task #{$task->id} updated.", ['id' => $task->id]);
        }

        return self::result(false, 'Unknown task action. Use list, create, show, update, or complete.');
    }

    /**
     * Conversations: list/show/create/send — membership-scoped via conversation_user pivot.
     * Uses conversation_chat / conversation_messages (canonical messaging tables).
     *
     * @param  array{action?: string, conversation_id?: int, body?: string, recipient_user_id?: int}  $data
     */
    public static function manageConversations(User $student, array $data): array
    {
        $student = self::resolveActor($student);
        $action = $data['action'] ?? 'list';

        if ($action === 'list') {
            $ids = DB::table('conversation_user')
                ->where('user_id', $student->id)
                ->orderByDesc('conversation_id')
                ->limit(20)
                ->pluck('conversation_id');

            $chats = DB::table('conversation_chat')
                ->whereIn('id', $ids)
                ->get(['id', 'uuid', 'last_message_at']);

            $lines = $chats->map(fn ($c) => "- Conversation #{$c->id}".($c->uuid ? " ({$c->uuid})" : ''))->implode("\n");

            return self::result(true, $lines !== '' ? "**Your conversations:**\n{$lines}" : 'You have no conversations.', [
                'count' => $chats->count(),
                'ids' => $chats->pluck('id')->map(fn ($id) => (int) $id)->all(),
            ]);
        }

        if ($action === 'show' || $action === 'send') {
            $conversationId = (int) ($data['conversation_id'] ?? 0);
            if ($conversationId < 1) {
                return self::result(false, 'conversation_id is required.');
            }

            $isMember = DB::table('conversation_user')
                ->where('conversation_id', $conversationId)
                ->where('user_id', $student->id)
                ->exists();

            if (! $isMember) {
                return self::result(false, 'Conversation not found or access denied.');
            }

            $conversation = DB::table('conversation_chat')->where('id', $conversationId)->first();
            if (! $conversation) {
                return self::result(false, 'Conversation not found or access denied.');
            }

            if ($action === 'show') {
                $messages = DB::table('conversation_messages')
                    ->where('conversation_id', $conversationId)
                    ->orderByDesc('id')
                    ->limit(20)
                    ->get(['id', 'user_id', 'body']);

                $lines = $messages->map(fn ($m) => "- [#{$m->id} user {$m->user_id}] ".Str::limit((string) $m->body, 80))->implode("\n");

                return self::result(true, $lines !== '' ? "**Conversation #{$conversationId}:**\n{$lines}" : "Conversation #{$conversationId} has no messages.", [
                    'id' => $conversationId,
                ]);
            }

            $v = Validator::make($data, [
                'body' => 'required|string|min:1|max:5000',
            ]);
            if ($v->fails()) {
                return self::result(false, $v->errors()->first());
            }

            $messageId = DB::table('conversation_messages')->insertGetId([
                'conversation_id' => $conversationId,
                'user_id' => $student->id,
                'body' => $data['body'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('conversation_chat')->where('id', $conversationId)->update([
                'last_message_at' => now(),
                'updated_at' => now(),
            ]);

            return self::result(true, "Message sent in conversation #{$conversationId} (message id {$messageId}).", [
                'conversation_id' => $conversationId,
                'message_id' => $messageId,
            ]);
        }

        if ($action === 'create') {
            $v = Validator::make($data, [
                'body' => 'required|string|min:1|max:5000',
                'recipient_user_id' => 'required|integer',
            ]);
            if ($v->fails()) {
                return self::result(false, $v->errors()->first());
            }

            $recipientId = (int) $data['recipient_user_id'];
            if ($recipientId === (int) $student->id) {
                return self::result(false, 'Cannot start a conversation with yourself.');
            }

            $recipient = User::where('id', $recipientId)
                ->where('school_id', $student->school_id)
                ->whereIn('usergroup_id', [5, 6, 3])
                ->first();

            if (! $recipient) {
                return self::result(false, 'Recipient not found in your school (must be classmate, teacher, or school admin).');
            }

            $conversationId = DB::table('conversation_chat')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
                'last_message_at' => now(),
            ]);

            DB::table('conversation_messages')->insert([
                'conversation_id' => $conversationId,
                'user_id' => $student->id,
                'body' => $data['body'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $now = now();
            DB::table('conversation_user')->insert([
                ['conversation_id' => $conversationId, 'user_id' => $student->id, 'created_at' => $now, 'updated_at' => $now],
                ['conversation_id' => $conversationId, 'user_id' => $recipient->id, 'created_at' => $now, 'updated_at' => $now],
            ]);

            return self::result(true, "Conversation created (id {$conversationId}) with user #{$recipient->id}.", [
                'id' => $conversationId,
            ]);
        }

        return self::result(false, 'Unknown conversation action. Use list, show, create, or send.');
    }
}
