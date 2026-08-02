<?php

namespace App\Services\Toshi;

use App\Helpers\SiteHelper;
use App\Models\Academics\TimetableSlot;
use App\Models\Homework;
use App\Models\HomeworkApproval;
use App\Models\StudentHomework;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/**
 * School Admin Batch 2 — timetable slots + homework oversight + student-homework review.
 *
 * Homework approve/update/destroy-adjacent paths use Gate::allows('homework-manage').
 * StudentHomework review uses Gate::allows('studentHomework-review').
 * Timetable uses school_id isolation (controller abort_if pattern; no dedicated Gate).
 * No destroy tools in this batch.
 */
class SchoolAcademicsOpsActionService
{
    private const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function listTimetableSlots(User $user, array $args = []): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $limit = max(1, min((int) ($args['limit'] ?? 30), 50));
        $query = TimetableSlot::where('school_id', $schoolId)
            ->with(['subject:id,name', 'teacher:id,name', 'section:id,name'])
            ->orderBy('day_of_week')
            ->orderBy('start_time');

        if (! empty($args['section_id'])) {
            $query->where('section_id', (int) $args['section_id']);
        }

        $slots = $query->limit($limit)->get();
        if ($slots->isEmpty()) {
            return self::ok('No timetable slots found.');
        }

        $lines = $slots->map(function (TimetableSlot $slot) {
            $day = self::DAY_NAMES[$slot->day_of_week] ?? (string) $slot->day_of_week;
            $start = substr((string) $slot->start_time, 0, 5);
            $end = substr((string) $slot->end_time, 0, 5);
            $subject = $slot->subject?->name ?? 'subject#'.$slot->subject_id;
            $teacher = $slot->teacher?->name ?? 'teacher#'.$slot->teacher_id;
            $section = $slot->section?->name ?? 'section#'.$slot->section_id;

            return "- #{$slot->id} {$day} {$start}–{$end} {$subject} / {$teacher} ({$section})".($slot->room ? " room={$slot->room}" : '');
        })->implode("\n");

        return self::ok("**Timetable slots:**\n{$lines}", $slots->toArray());
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function createTimetableSlot(User $user, array $args): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $year = SiteHelper::getAcademicYear($schoolId);
        if (! $year) {
            return self::fail('No academic year configured.');
        }

        $validator = Validator::make($args, [
            'section_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'teacher_id' => 'required|integer',
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
            'academic_term_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return self::fail($validator->errors()->first());
        }

        $data = $validator->validated();
        $data['school_id'] = $schoolId;
        $data['academic_year_id'] = $year->id;

        $conflict = TimetableSlot::detectConflict(
            $schoolId,
            (int) $data['section_id'],
            (int) $data['teacher_id'],
            (int) $data['day_of_week'],
            $data['start_time'],
            $data['end_time']
        );
        if ($conflict) {
            return self::fail($conflict);
        }

        $slot = TimetableSlot::create($data);
        if ((int) $slot->school_id !== (int) $schoolId) {
            $slot->delete();

            return self::fail('Slot failed school isolation after create.');
        }

        $slot->syncCalendarEvent();

        return self::ok("Timetable slot #{$slot->id} created.", ['id' => $slot->id]);
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function updateTimetableSlot(User $user, array $args): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $slot = TimetableSlot::where('id', (int) ($args['slot_id'] ?? 0))->first();
        if (! $slot) {
            return self::fail('Timetable slot not found.');
        }

        if ((int) $slot->school_id !== (int) $schoolId) {
            return self::fail('You are not authorized to update this timetable slot.');
        }

        $payload = [
            'section_id' => $args['section_id'] ?? $slot->section_id,
            'subject_id' => $args['subject_id'] ?? $slot->subject_id,
            'teacher_id' => $args['teacher_id'] ?? $slot->teacher_id,
            'day_of_week' => $args['day_of_week'] ?? $slot->day_of_week,
            'start_time' => $args['start_time'] ?? substr((string) $slot->start_time, 0, 5),
            'end_time' => $args['end_time'] ?? substr((string) $slot->end_time, 0, 5),
            'room' => array_key_exists('room', $args) ? $args['room'] : $slot->room,
            'academic_term_id' => $args['academic_term_id'] ?? $slot->academic_term_id,
        ];

        $validator = Validator::make($payload, [
            'section_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'teacher_id' => 'required|integer',
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
            'academic_term_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return self::fail($validator->errors()->first());
        }

        $data = $validator->validated();
        $conflict = TimetableSlot::detectConflict(
            $schoolId,
            (int) $data['section_id'],
            (int) $data['teacher_id'],
            (int) $data['day_of_week'],
            $data['start_time'],
            $data['end_time'],
            $slot->id
        );
        if ($conflict) {
            return self::fail($conflict);
        }

        $slot->update($data);
        $slot->syncCalendarEvent();

        return self::ok("Timetable slot #{$slot->id} updated.", ['id' => $slot->id]);
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function listHomework(User $user, array $args = []): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $limit = max(1, min((int) ($args['limit'] ?? 20), 50));
        $query = Homework::where('school_id', $schoolId)
            ->with('homeworkApproval')
            ->orderByDesc('id')
            ->limit($limit);

        if (! empty($args['status'])) {
            $status = (string) $args['status'];
            $query->whereHas('homeworkApproval', fn ($q) => $q->where('status', $status));
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return self::ok('No homework found.');
        }

        $lines = $rows->map(function (Homework $hw) {
            $status = $hw->homeworkApproval?->status ?? 'none';

            return "- #{$hw->id} [{$status}] classLink#{$hw->standardLink_id} subject#{$hw->subject_id}: ".mb_strimwidth((string) $hw->description, 0, 60, '…');
        })->implode("\n");

        return self::ok("**Homework:**\n{$lines}", $rows->toArray());
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function createHomework(User $user, array $args): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $year = SiteHelper::getAcademicYear($schoolId);
        if (! $year) {
            return self::fail('No academic year configured.');
        }

        $validator = Validator::make($args, [
            'standardLink_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'description' => 'required|string|min:3|max:5000',
            'date' => 'required|date_format:Y-m-d',
            'submission_date' => 'required|date_format:Y-m-d|after_or_equal:date',
            'teacher_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return self::fail($validator->errors()->first());
        }

        $data = $validator->validated();

        $homework = new Homework;
        $homework->school_id = $schoolId;
        $homework->academic_year_id = $year->id;
        $homework->standardLink_id = $data['standardLink_id'];
        $homework->subject_id = $data['subject_id'];
        $homework->teacher_id = $data['teacher_id'] ?? $user->id;
        $homework->description = $data['description'];
        $homework->date = $data['date'];
        $homework->submission_date = $data['submission_date'];
        $homework->created_by = $user->id;
        $homework->save();

        if (! Gate::forUser($user)->allows('homework-manage', $homework)) {
            $homework->delete();

            return self::fail('Homework failed homework-manage authorization after create.');
        }

        HomeworkApproval::create([
            'homework_id' => $homework->id,
            'status' => 'pending',
        ]);

        return self::ok("Homework #{$homework->id} created (pending approval).", ['id' => $homework->id]);
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function updateHomework(User $user, array $args): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $homework = Homework::where('id', (int) ($args['homework_id'] ?? 0))->first();
        if (! $homework) {
            return self::fail('Homework not found.');
        }

        if (! Gate::forUser($user)->allows('homework-manage', $homework)) {
            return self::fail('You are not authorized to update this homework.');
        }

        if ((int) $homework->school_id !== (int) $schoolId) {
            return self::fail('Homework does not belong to your school.');
        }

        if (isset($args['description']) && is_string($args['description']) && $args['description'] !== '') {
            $homework->description = $args['description'];
        }
        if (isset($args['date']) && is_string($args['date']) && $args['date'] !== '') {
            $homework->date = $args['date'];
        }
        if (isset($args['submission_date']) && is_string($args['submission_date']) && $args['submission_date'] !== '') {
            $homework->submission_date = $args['submission_date'];
        }
        if (isset($args['standardLink_id'])) {
            $homework->standardLink_id = (int) $args['standardLink_id'];
        }
        if (isset($args['subject_id'])) {
            $homework->subject_id = (int) $args['subject_id'];
        }
        if (isset($args['teacher_id'])) {
            $homework->teacher_id = (int) $args['teacher_id'];
        }

        $homework->updated_by = $user->id;
        $homework->save();

        return self::ok("Homework #{$homework->id} updated.", ['id' => $homework->id]);
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function approveHomework(User $user, array $args): array
    {
        return self::setHomeworkApprovalStatus($user, $args, 'approved');
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function rejectHomework(User $user, array $args): array
    {
        return self::setHomeworkApprovalStatus($user, $args, 'rejected');
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    private static function setHomeworkApprovalStatus(User $user, array $args, string $status): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $homework = Homework::where('id', (int) ($args['homework_id'] ?? 0))->first();
        if (! $homework) {
            return self::fail('Homework not found.');
        }

        if (! Gate::forUser($user)->allows('homework-manage', $homework)) {
            return self::fail('You are not authorized to manage this homework.');
        }

        if ((int) $homework->school_id !== (int) $schoolId) {
            return self::fail('Homework does not belong to your school.');
        }

        $approval = HomeworkApproval::firstOrCreate(
            ['homework_id' => $homework->id],
            ['status' => 'pending']
        );

        $approval->comments = $args['comments'] ?? $approval->comments;
        $approval->approved_by = $user->id;
        $approval->approved_at = date('Y-m-d');
        $approval->status = $status;
        $approval->save();

        return self::ok("Homework #{$homework->id} {$status}.", ['id' => $homework->id, 'status' => $status]);
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function listStudentHomework(User $user, array $args): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $homeworkId = (int) ($args['homework_id'] ?? 0);
        $homework = Homework::where('id', $homeworkId)->first();
        if (! $homework) {
            return self::fail('Homework not found.');
        }

        if (! Gate::forUser($user)->allows('studentHomework-review', $homework)) {
            return self::fail('You are not authorized to review submissions for this homework.');
        }

        if ((int) $homework->school_id !== (int) $schoolId) {
            return self::fail('Homework does not belong to your school.');
        }

        $limit = max(1, min((int) ($args['limit'] ?? 20), 50));
        $rows = StudentHomework::where('homework_id', $homework->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return self::ok("No student submissions for homework #{$homework->id}.");
        }

        $lines = $rows->map(fn (StudentHomework $sh) => "- #{$sh->id} student#{$sh->user_id} status={$sh->status}")->implode("\n");

        return self::ok("**Student homework for #{$homework->id}:**\n{$lines}", $rows->toArray());
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function showStudentHomework(User $user, array $args): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $row = StudentHomework::with('homework')->where('id', (int) ($args['student_homework_id'] ?? 0))->first();
        if (! $row) {
            return self::fail('Student homework not found.');
        }

        if (! Gate::forUser($user)->allows('studentHomework-review', $row)) {
            return self::fail('You are not authorized to review this submission.');
        }

        if ((int) ($row->homework?->school_id) !== (int) $schoolId) {
            return self::fail('Submission does not belong to your school.');
        }

        $message = "**Student homework #{$row->id}**\n"
            ."- homework_id: {$row->homework_id}\n"
            ."- student_id: {$row->user_id}\n"
            ."- status: {$row->status}\n"
            .'- comments: '.($row->comments ?: '(none)');

        return self::ok($message, $row->toArray());
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function updateStudentHomework(User $user, array $args): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $row = StudentHomework::with('homework')->where('id', (int) ($args['student_homework_id'] ?? 0))->first();
        if (! $row) {
            return self::fail('Student homework not found.');
        }

        if (! Gate::forUser($user)->allows('studentHomework-review', $row)) {
            return self::fail('You are not authorized to review this submission.');
        }

        if ((int) ($row->homework?->school_id) !== (int) $schoolId) {
            return self::fail('Submission does not belong to your school.');
        }

        if (($row->status ?? '') === 'checked') {
            return self::fail('Homework already checked.');
        }

        $row->comments = $args['comments'] ?? $row->comments;
        $row->checked_by = $user->id;
        $row->checked_on = date('Y-m-d');
        $row->status = 'checked';
        $row->save();

        return self::ok("Student homework #{$row->id} marked checked.", ['id' => $row->id]);
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return array{success: bool, message: string, data?: mixed}
     */
    private static function ok(string $message, ?array $data = null): array
    {
        $out = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $out['data'] = $data;
        }

        return $out;
    }

    /**
     * @return array{success: bool, message: string}
     */
    private static function fail(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}
