<?php

namespace App\Services\Toshi;

use App\Helpers\SiteHelper;
use App\Models\Academics\Marks;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Events;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\LessonPlan;
use App\Models\NoticeBoard;
use App\Models\Post;
use App\Models\StudentAssignment;
use App\Models\Task;
use App\Models\TeacherLeaveApplication;
use App\Models\Teacherlink;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher-scoped Toshi mutations / reads (ug5).
 * Mirrors teacher panel controllers — does not reuse ug3 ToshiActionService write paths.
 */
class TeacherActionService
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

    public static function markAttendance(User $teacher, array $data): array
    {
        $v = Validator::make($data, [
            'student' => 'required|string',
            'date' => 'required|date_format:Y-m-d',
            'status' => 'required|in:present,absent,late,excused',
            'standardLink_id' => 'nullable|integer',
            'session' => 'nullable|string',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $schoolId = (int) $teacher->school_id;
        $student = self::findStudentInSchool($schoolId, $data['student']);
        if (! $student) {
            return self::result(false, "Student not found: {$data['student']}");
        }

        $statusMap = ['present' => 1, 'absent' => 0, 'late' => 1, 'excused' => 1];

        Attendance::updateOrCreate(
            [
                'school_id' => $schoolId,
                'user_id' => $student->id,
                'date' => $data['date'],
                'session' => $data['session'] ?? 'forenoon',
            ],
            [
                'status' => $statusMap[$data['status']],
                'remarks' => $data['status'],
                'standardLink_id' => $data['standardLink_id'] ?? null,
                'academic_year_id' => optional(SiteHelper::getAcademicYear($schoolId))->id,
                'recorded_by' => $teacher->id,
            ]
        );

        return self::result(true, "Attendance recorded for **{$student->name}** on {$data['date']} as {$data['status']}.");
    }

    public static function enterMarks(User $teacher, array $data): array
    {
        $v = Validator::make($data, [
            'exam_id' => 'required|integer',
            'student' => 'required|string',
            'marks' => 'required|numeric|min:0',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $schoolId = (int) $teacher->school_id;
        $exam = Exam::where('school_id', $schoolId)->where('id', $data['exam_id'])->first();
        if (! $exam) {
            return self::result(false, "Exam not found: {$data['exam_id']}");
        }
        if ((int) ($exam->teacher_id ?? 0) !== (int) $teacher->id) {
            return self::result(false, 'You can only enter marks for exams assigned to you.');
        }

        $student = self::findStudentInSchool($schoolId, $data['student']);
        if (! $student) {
            return self::result(false, "Student not found: {$data['student']}");
        }

        Marks::updateOrCreate(
            [
                'school_id' => $schoolId,
                'exam_id' => $exam->id,
                'student_id' => $student->id,
            ],
            [
                'marks' => $data['marks'],
                'teacher_id' => $teacher->id,
                'subject_id' => $exam->subject_id,
                'section_id' => $exam->section_id ?? null,
            ]
        );

        return self::result(true, "Marks **{$data['marks']}** saved for **{$student->name}** on exam #{$exam->id}.");
    }

    public static function createLessonPlan(User $teacher, array $data): array
    {
        $v = Validator::make($data, [
            'standardLink_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'title' => 'required|string|min:3',
            'description' => 'nullable|string',
            'unit_no' => 'nullable|string',
            'unit_name' => 'nullable|string',
            'duration' => 'nullable|string',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $link = Teacherlink::where('teacher_id', $teacher->id)
            ->where('standardLink_id', $data['standardLink_id'])
            ->where('subject_id', $data['subject_id'])
            ->first();
        if (! $link) {
            return self::result(false, 'You are not linked to that class/subject (Teacherlink required).');
        }

        $plan = LessonPlan::create([
            'school_id' => $teacher->school_id,
            'teacher_link_id' => $link->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'unit_no' => $data['unit_no'] ?? null,
            'unit_name' => $data['unit_name'] ?? null,
            'duration' => $data['duration'] ?? null,
            'status' => 'pending',
        ]);

        return self::result(true, "Lesson plan **{$plan->title}** created (id {$plan->id}).", ['id' => $plan->id]);
    }

    public static function createAssignment(User $teacher, array $data): array
    {
        $v = Validator::make($data, [
            'standardLink_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'title' => 'required|string|min:3',
            'description' => 'nullable|string',
            'assigned_date' => 'required|date_format:Y-m-d',
            'submission_date' => 'required|date_format:Y-m-d|after_or_equal:assigned_date',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        if (! self::teacherLinked($teacher, (int) $data['standardLink_id'], (int) $data['subject_id'])) {
            return self::result(false, 'You are not linked to that class/subject.');
        }

        $row = Assignment::create([
            'school_id' => $teacher->school_id,
            'academic_year_id' => optional(SiteHelper::getAcademicYear($teacher->school_id))->id,
            'standardLink_id' => $data['standardLink_id'],
            'subject_id' => $data['subject_id'],
            'teacher_id' => $teacher->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'assigned_date' => $data['assigned_date'],
            'submission_date' => $data['submission_date'],
            'status' => 'pending',
        ]);

        return self::result(true, "Assignment **{$row->title}** created (id {$row->id}).", ['id' => $row->id]);
    }

    public static function createHomework(User $teacher, array $data): array
    {
        $v = Validator::make($data, [
            'standardLink_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'description' => 'required|string|min:3',
            'date' => 'required|date_format:Y-m-d',
            'submission_date' => 'required|date_format:Y-m-d|after_or_equal:date',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        if (! self::teacherLinked($teacher, (int) $data['standardLink_id'], (int) $data['subject_id'])) {
            return self::result(false, 'You are not linked to that class/subject.');
        }

        $row = Homework::create([
            'school_id' => $teacher->school_id,
            'academic_year_id' => optional(SiteHelper::getAcademicYear($teacher->school_id))->id,
            'standardLink_id' => $data['standardLink_id'],
            'subject_id' => $data['subject_id'],
            'teacher_id' => $teacher->id,
            'description' => $data['description'],
            'date' => $data['date'],
            'submission_date' => $data['submission_date'],
            'created_by' => $teacher->id,
        ]);

        return self::result(true, "Homework created (id {$row->id}).", ['id' => $row->id]);
    }

    public static function applyLeave(User $teacher, array $data): array
    {
        $v = Validator::make($data, [
            'from_date' => 'required|date_format:Y-m-d',
            'to_date' => 'required|date_format:Y-m-d|after_or_equal:from_date',
            'reason_id' => 'required|integer',
            'leave_type_id' => 'required|integer',
            'session' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $row = TeacherLeaveApplication::create([
            'school_id' => $teacher->school_id,
            'academic_year_id' => optional(SiteHelper::getAcademicYear($teacher->school_id))->id,
            'user_id' => $teacher->id,
            'from_date' => $data['from_date'].' 00:00:00',
            'to_date' => $data['to_date'].' 23:59:59',
            'reason_id' => $data['reason_id'],
            'leave_type_id' => $data['leave_type_id'],
            'session' => $data['session'] ?? 'full',
            'remarks' => $data['remarks'] ?? null,
            'status' => 'pending',
        ]);

        return self::result(true, "Leave request submitted (id {$row->id}) from {$data['from_date']} to {$data['to_date']}.", ['id' => $row->id]);
    }

    public static function createClassWallPost(User $teacher, array $data): array
    {
        $v = Validator::make($data, [
            'description' => 'required|string|min:3',
            'visibility' => 'nullable|string',
            'standardLink_id' => 'nullable|integer',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $row = Post::create([
            'school_id' => $teacher->school_id,
            'academic_year_id' => optional(SiteHelper::getAcademicYear($teacher->school_id))->id,
            'description' => $data['description'],
            'visibility' => $data['visibility'] ?? 'all',
            'visible_for' => isset($data['standardLink_id']) ? (string) $data['standardLink_id'] : null,
            'created_by' => $teacher->id,
            'posted_at' => now(),
            'is_posted' => 1,
            'status' => 1,
            'entity_id' => $teacher->id,
            'entity_name' => 'teacher',
        ]);

        return self::result(true, "Class wall post published (id {$row->id}).", ['id' => $row->id]);
    }

    public static function createTask(User $teacher, array $data): array
    {
        $v = Validator::make($data, [
            'title' => 'required|string|min:3',
            'to_do_list' => 'nullable|string',
            'task_date' => 'required|date_format:Y-m-d',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $row = Task::create([
            'school_id' => $teacher->school_id,
            'academic_year_id' => optional(SiteHelper::getAcademicYear($teacher->school_id))->id,
            'user_id' => $teacher->id,
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

    public static function viewStudents(User $teacher): array
    {
        $linkIds = Teacherlink::where('teacher_id', $teacher->id)->pluck('standardLink_id')->unique()->filter();

        $query = User::where('school_id', $teacher->school_id)->where('usergroup_id', 6)->orderBy('name')->limit(50);

        $students = $query->get(['id', 'name', 'email']);
        $note = $linkIds->isEmpty()
            ? ' (no Teacherlink rows — showing school students sample)'
            : '';

        $lines = $students->map(fn ($s) => "- {$s->name} ({$s->email})")->implode("\n");

        return self::result(true, $lines !== '' ? "**Students{$note}:**\n{$lines}" : 'No students found.', [
            'count' => $students->count(),
        ]);
    }

    public static function viewTimetable(User $teacher): array
    {
        $links = Teacherlink::where('teacher_id', $teacher->id)->with('standardLink')->limit(20)->get();
        if ($links->isEmpty()) {
            return self::result(true, 'No Teacherlink class/subject rows — timetable empty for your account.');
        }

        $lines = $links->map(function ($l) {
            return '- classLink#'.($l->standardLink_id ?? '?').' subject#'.($l->subject_id ?? '?');
        })->implode("\n");

        return self::result(true, "**Your teaching links (timetable basis):**\n{$lines}");
    }

    public static function viewEvents(User $teacher): array
    {
        $events = Events::where('school_id', $teacher->school_id)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'title']);

        $lines = $events->map(fn ($e) => "- {$e->title} (#{$e->id})")->implode("\n");

        return self::result(true, $lines !== '' ? "**Events:**\n{$lines}" : 'No events found.');
    }

    public static function viewNoticeboard(User $teacher): array
    {
        $notices = NoticeBoard::where('school_id', $teacher->school_id)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'title', 'type']);

        $lines = $notices->map(fn ($n) => '- '.($n->title ?? 'Notice')." (#{$n->id})")->implode("\n");

        return self::result(true, $lines !== '' ? "**Notices:**\n{$lines}" : 'No notices found.');
    }

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public static function listStudentAssignments(User $teacher, array $args): array
    {
        $assignmentId = (int) ($args['assignment_id'] ?? 0);
        $assignment = Assignment::where('id', $assignmentId)->first();
        if (! $assignment) {
            return self::result(false, 'Assignment not found.');
        }

        if (! Gate::forUser($teacher)->allows('studentAssignment-review', $assignment)) {
            return self::result(false, 'You are not authorized to review submissions for this assignment.');
        }

        $limit = max(1, min((int) ($args['limit'] ?? 20), 50));
        $status = $args['status'] ?? null;
        $query = StudentAssignment::where('assignment_id', $assignment->id)->orderByDesc('id')->limit($limit);
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return self::result(true, "No student submissions for assignment #{$assignment->id}.");
        }

        $lines = $rows->map(fn (StudentAssignment $sa) => "- #{$sa->id} student#{$sa->user_id} status={$sa->status}")->implode("\n");

        return self::result(true, "**Student assignments for #{$assignment->id}:**\n{$lines}", $rows->toArray());
    }

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public static function showStudentAssignment(User $teacher, array $args): array
    {
        $row = StudentAssignment::with('assignment')->where('id', (int) ($args['student_assignment_id'] ?? 0))->first();
        if (! $row) {
            return self::result(false, 'Student assignment not found.');
        }

        if (! Gate::forUser($teacher)->allows('studentAssignment-review', $row)) {
            return self::result(false, 'You are not authorized to review this submission.');
        }

        $message = "**Student assignment #{$row->id}**\n"
            ."- assignment_id: {$row->assignment_id}\n"
            ."- student_id: {$row->user_id}\n"
            ."- status: {$row->status}\n"
            .'- obtained_marks: '.($row->obtained_marks ?? '(none)')."\n"
            .'- comments: '.($row->comments ?: '(none)');

        return self::result(true, $message, $row->toArray());
    }

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public static function markStudentAssignment(User $teacher, array $args): array
    {
        $v = Validator::make($args, [
            'student_assignment_id' => 'required|integer',
            'obtained_marks' => 'required|numeric|min:0',
            'comments' => 'nullable|string',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $row = StudentAssignment::with('assignment')->where('id', (int) $args['student_assignment_id'])->first();
        if (! $row) {
            return self::result(false, 'Student assignment not found.');
        }

        if (! Gate::forUser($teacher)->allows('studentAssignment-review', $row)) {
            return self::result(false, 'You are not authorized to mark this submission.');
        }

        $max = (float) ($row->assignment?->marks ?? 0);
        if ((float) $args['obtained_marks'] > $max) {
            return self::result(false, "Mark cannot be greater than {$max}.");
        }

        if (($row->status ?? '') === 'completed') {
            return self::result(false, 'Submission already marked completed.');
        }

        $row->obtained_marks = $args['obtained_marks'];
        $row->comments = $args['comments'] ?? $row->comments;
        $row->marks_given_by = $teacher->id;
        $row->marks_given_on = date('Y-m-d');
        $row->status = 'completed';
        $row->save();

        return self::result(true, "Student assignment #{$row->id} marked completed.", ['id' => $row->id]);
    }

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public static function listMyLeaves(User $teacher, array $args = []): array
    {
        $limit = max(1, min((int) ($args['limit'] ?? 20), 50));
        $schoolId = (int) $teacher->school_id;
        $academicYear = SiteHelper::getAcademicYear($schoolId);

        $query = TeacherLeaveApplication::where('user_id', $teacher->id)
            ->where('school_id', $schoolId)
            ->orderByDesc('id')
            ->limit($limit);

        if ($academicYear) {
            $query->where('academic_year_id', $academicYear->id);
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return self::result(true, 'You have no leave applications.');
        }

        $lines = $rows->map(function (TeacherLeaveApplication $leave) {
            $from = optional($leave->from_date)->format('Y-m-d') ?? (string) $leave->from_date;
            $to = optional($leave->to_date)->format('Y-m-d') ?? (string) $leave->to_date;

            return "- #{$leave->id} {$from}→{$to} status={$leave->status}";
        })->implode("\n");

        return self::result(true, "**My leave applications:**\n{$lines}", $rows->toArray());
    }

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public static function showMyLeave(User $teacher, array $args): array
    {
        $leave = TeacherLeaveApplication::where('id', (int) ($args['leave_id'] ?? 0))->first();
        if (! $leave) {
            return self::result(false, 'Leave application not found.');
        }

        if (! Gate::forUser($teacher)->allows('teacher-leave', $leave)) {
            return self::result(false, 'You are not authorized to view this leave application.');
        }

        $from = optional($leave->from_date)->format('Y-m-d H:i') ?? (string) $leave->from_date;
        $to = optional($leave->to_date)->format('Y-m-d H:i') ?? (string) $leave->to_date;
        $message = "**Leave #{$leave->id}**\n"
            ."- from: {$from}\n"
            ."- to: {$to}\n"
            ."- status: {$leave->status}\n"
            .'- remarks: '.($leave->remarks ?: '(none)');

        return self::result(true, $message, $leave->toArray());
    }

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public static function cancelMyLeave(User $teacher, array $args): array
    {
        $leave = TeacherLeaveApplication::where('id', (int) ($args['leave_id'] ?? 0))->first();
        if (! $leave) {
            return self::result(false, 'Leave application not found.');
        }

        if (! Gate::forUser($teacher)->allows('teacher-leave', $leave)) {
            return self::result(false, 'You are not authorized to cancel this leave application.');
        }

        if (($leave->status ?? '') !== 'pending') {
            return self::result(false, 'Only pending leave applications can be cancelled.');
        }

        $leave->status = 'cancelled';
        $leave->save();
        $leave->delete();

        return self::result(true, "Leave application #{$leave->id} cancelled.", ['id' => $leave->id]);
    }

    private static function teacherLinked(User $teacher, int $standardLinkId, int $subjectId): bool
    {
        return Teacherlink::where('teacher_id', $teacher->id)
            ->where('standardLink_id', $standardLinkId)
            ->where('subject_id', $subjectId)
            ->exists();
    }

    private static function findStudentInSchool(int $schoolId, string $identifier): ?User
    {
        return User::where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where(function ($q) use ($identifier) {
                $q->where('email', $identifier)
                    ->orWhere('name', 'LIKE', '%'.$identifier.'%');
                if (is_numeric($identifier)) {
                    $q->orWhere('id', (int) $identifier);
                }
            })
            ->first();
    }
}
