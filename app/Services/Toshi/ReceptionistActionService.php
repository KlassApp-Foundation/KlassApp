<?php

namespace App\Services\Toshi;

use App\Helpers\SiteHelper;
use App\Models\CallLog;
use App\Models\Events;
use App\Models\NoticeBoard;
use App\Models\PostalRecord;
use App\Models\Task;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Support\Facades\Validator;

/**
 * Receptionist-scoped Toshi mutations / reads (ug10).
 * Mirrors receptionist panel controllers — does not reuse ug3 ToshiActionService write paths.
 *
 * Write surfaces (Tier-2 ConfirmsBeforeWrite at tool layer):
 *   manageVisitorLog, manageCallLog, managePostalRecord, createTask
 * Read-only:
 *   viewDashboard, viewEvents, viewNoticeboard
 */
class ReceptionistActionService
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

    public static function manageVisitorLog(User $receptionist, array $data): array
    {
        $v = Validator::make($data, [
            'name' => 'required|string|max:100',
            'contact_number' => 'required|string|max:30',
            'visiting_purpose' => 'required|string|max:255',
            'date_of_visit' => 'required|date_format:Y-m-d',
            'relation' => 'nullable|string|in:parent,other,others',
            'entry_time' => 'nullable|date_format:H:i',
            'exit_time' => 'nullable|date_format:H:i',
            'email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'number_of_visitors' => 'nullable|integer|min:1',
            'employee_id' => 'nullable|integer',
            'remark' => 'nullable|string|max:1000',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $schoolId = (int) $receptionist->school_id;
        $academicYear = SiteHelper::getAcademicYear($schoolId);
        if (! $academicYear) {
            return self::result(false, 'No active academic year for your school.');
        }

        if (! empty($data['employee_id'])) {
            $employee = User::where('school_id', $schoolId)->where('id', $data['employee_id'])->first();
            if (! $employee) {
                return self::result(false, 'Employee not found in your school.');
            }
        }

        $relation = $data['relation'] ?? 'other';
        if ($relation === 'others') {
            $relation = 'other';
        }

        $row = new VisitorLog;
        $row->school_id = $schoolId;
        $row->academic_year_id = $academicYear->id;
        $row->relation = $relation;
        $row->name = $data['name'];
        $row->contact_number = $data['contact_number'];
        $row->email = $data['email'] ?? null;
        $row->company_name = $data['company_name'] ?? null;
        $row->address = $data['address'] ?? null;
        $row->visiting_purpose = $data['visiting_purpose'];
        $row->number_of_visitors = $data['number_of_visitors'] ?? 1;
        $row->employee_id = $data['employee_id'] ?? null;
        $row->date_of_visit = $data['date_of_visit'];
        $row->entry_time = $data['entry_time'] ?? null;
        $row->exit_time = $data['exit_time'] ?? null;
        $row->remark = $data['remark'] ?? null;
        $row->save();

        return self::result(true, "Visitor **{$row->name}** logged for {$row->date_of_visit} (id {$row->id}).", [
            'id' => $row->id,
        ]);
    }

    public static function manageCallLog(User $receptionist, array $data): array
    {
        $v = Validator::make($data, [
            'name' => 'required|string|max:100',
            'call_type' => 'required|in:incoming,outgoing',
            'call_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'calling_purpose' => 'nullable|string|max:255',
            'incoming_number' => 'nullable|string|max:30',
            'outgoing_number' => 'nullable|string|max:30',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string|max:2000',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        if ($data['call_type'] === 'outgoing') {
            if (empty($data['calling_purpose'])) {
                return self::result(false, 'calling_purpose is required for outgoing calls.');
            }
            if (empty($data['outgoing_number'])) {
                return self::result(false, 'outgoing_number is required for outgoing calls.');
            }
        } elseif (empty($data['incoming_number'])) {
            return self::result(false, 'incoming_number is required for incoming calls.');
        }

        $schoolId = (int) $receptionist->school_id;
        $academicYear = SiteHelper::getAcademicYear($schoolId);
        if (! $academicYear) {
            return self::result(false, 'No active academic year for your school.');
        }

        $duration = null;
        if (! empty($data['start_time']) && ! empty($data['end_time'])) {
            $end = \DateTime::createFromFormat('H:i', $data['end_time']);
            $start = \DateTime::createFromFormat('H:i', $data['start_time']);
            if ($end && $start) {
                $duration = $end->diff($start)->format('%h:%i');
            }
        }

        $row = new CallLog;
        $row->school_id = $schoolId;
        $row->academic_year_id = $academicYear->id;
        $row->name = $data['name'];
        $row->calling_purpose = $data['calling_purpose'] ?? null;
        $row->call_type = $data['call_type'];
        $row->incoming_number = $data['incoming_number'] ?? null;
        $row->outgoing_number = $data['outgoing_number'] ?? null;
        $row->call_date = $data['call_date'];
        $row->start_time = $data['start_time'] ?? null;
        $row->end_time = $data['end_time'] ?? null;
        $row->duration = $duration;
        $row->description = $data['description'] ?? null;
        $row->entry_by = $receptionist->name;
        $row->save();

        return self::result(true, "Call log (**{$row->call_type}**) for **{$row->name}** saved (id {$row->id}).", [
            'id' => $row->id,
        ]);
    }

    public static function managePostalRecord(User $receptionist, array $data): array
    {
        $v = Validator::make($data, [
            'type' => 'required|string|max:50',
            'confidential' => 'required|in:0,1,yes,no,true,false',
            'description' => 'required|string|max:2000',
            'postal_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'post_type' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'sender_title' => 'nullable|string|max:255',
            'sender_address' => 'nullable|string|max:500',
            'receiver_title' => 'nullable|string|max:255',
            'receiver_address' => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return self::result(false, $v->errors()->first());
        }

        $schoolId = (int) $receptionist->school_id;
        $academicYear = SiteHelper::getAcademicYear($schoolId);
        if (! $academicYear) {
            return self::result(false, 'No active academic year for your school.');
        }

        $confidential = $data['confidential'];
        if (in_array($confidential, ['yes', 'true', true, 1, '1'], true)) {
            $confidential = 1;
        } elseif (in_array($confidential, ['no', 'false', false, 0, '0'], true)) {
            $confidential = 0;
        }

        $row = new PostalRecord;
        $row->school_id = $schoolId;
        $row->academic_year_id = $academicYear->id;
        $row->type = $data['type'];
        $row->post_type = $data['post_type'] ?? $data['type'];
        $row->reference_number = $data['reference_number'] ?? null;
        $row->confidential = $confidential;
        $row->sender_title = $data['sender_title'] ?? null;
        $row->sender_address = $data['sender_address'] ?? null;
        $row->receiver_title = $data['receiver_title'] ?? null;
        $row->receiver_address = $data['receiver_address'] ?? null;
        $row->postal_date = $data['postal_date'];
        $row->description = $data['description'];
        $row->attachment = '';
        $row->entry_by = $receptionist->name;
        $row->save();

        return self::result(true, "Postal record (**{$row->type}**) logged for {$row->postal_date} (id {$row->id}).", [
            'id' => $row->id,
        ]);
    }

    public static function viewDashboard(User $receptionist): array
    {
        $schoolId = (int) $receptionist->school_id;
        $academicYear = SiteHelper::getAcademicYear($schoolId);

        $studentCount = User::where('school_id', $schoolId)->where('usergroup_id', 6)->count();
        $teacherCount = User::where('school_id', $schoolId)->where('usergroup_id', 5)->count();
        $visitorCount = VisitorLog::where('school_id', $schoolId)->count();
        $callCount = CallLog::where('school_id', $schoolId)->count();
        $postalCount = PostalRecord::where('school_id', $schoolId)->count();
        $pendingTasks = Task::where('school_id', $schoolId)->where('task_status', 0)->count();

        $eventsNote = '';
        if ($academicYear) {
            $events = Events::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYear->id)
                ->where('category', '!=', 'holidays')
                ->where('end_date', '>', now())
                ->count();
            $notices = NoticeBoard::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYear->id)
                ->count();
            $eventsNote = "\n- Upcoming events: {$events}\n- Notices: {$notices}";
        }

        $msg = "**Receptionist dashboard**\n"
            ."- Students: {$studentCount}\n"
            ."- Teachers: {$teacherCount}\n"
            ."- Visitor logs: {$visitorCount}\n"
            ."- Call logs: {$callCount}\n"
            ."- Postal records: {$postalCount}\n"
            ."- Pending tasks: {$pendingTasks}"
            .$eventsNote;

        return self::result(true, $msg, [
            'students' => $studentCount,
            'teachers' => $teacherCount,
            'visitors' => $visitorCount,
            'calls' => $callCount,
            'postal' => $postalCount,
        ]);
    }

    public static function viewEvents(User $receptionist): array
    {
        $events = Events::where('school_id', $receptionist->school_id)
            ->where('category', '!=', 'holidays')
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'title']);

        $lines = $events->map(fn ($e) => "- {$e->title} (#{$e->id})")->implode("\n");

        return self::result(true, $lines !== '' ? "**Events:**\n{$lines}" : 'No events found.');
    }

    public static function viewNoticeboard(User $receptionist): array
    {
        $query = NoticeBoard::where('school_id', $receptionist->school_id)
            ->orderByDesc('id')
            ->limit(20);

        $academicYear = SiteHelper::getAcademicYear((int) $receptionist->school_id);
        if ($academicYear) {
            $query->where('academic_year_id', $academicYear->id);
        }

        $notices = $query->get(['id', 'title', 'type']);
        $lines = $notices->map(fn ($n) => '- '.($n->title ?? 'Notice')." (#{$n->id})")->implode("\n");

        return self::result(true, $lines !== '' ? "**Notices:**\n{$lines}" : 'No notices found.');
    }

    public static function createTask(User $receptionist, array $data): array
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
            'school_id' => $receptionist->school_id,
            'academic_year_id' => optional(SiteHelper::getAcademicYear($receptionist->school_id))->id,
            'user_id' => $receptionist->id,
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
}
