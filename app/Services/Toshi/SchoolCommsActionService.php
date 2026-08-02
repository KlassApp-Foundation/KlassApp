<?php

namespace App\Services\Toshi;

use App\Helpers\SiteHelper;
use App\Models\Events;
use App\Models\NoticeBoard;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/**
 * School Admin Batch 1 — notices / events / holidays.
 *
 * Notices: same `notice_board` / NoticeBoard model as Receptionist ViewNoticeboardTool
 * (ReceptionistActionService::viewNoticeboard). Do not invent a parallel store.
 *
 * Holidays: Events rows with category = 'holidays' (Admin HolidaysController pattern).
 * Events tools operate on non-holiday categories; both use Gate::allows('event', …)
 * for school_id scoping (post-#131; destroy stays portal/event-destroy only).
 */
class SchoolCommsActionService
{
    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function listNotices(User $user, int $limit = 20): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $year = SiteHelper::getAcademicYear($schoolId);
        $query = NoticeBoard::where('school_id', $schoolId);
        if ($year) {
            $query->where('academic_year_id', $year->id);
        }

        $notices = $query->orderByDesc('id')->limit(max(1, min($limit, 50)))->get([
            'id', 'title', 'type', 'publish_date', 'expire_date', 'status', 'standardLink_id',
        ]);

        if ($notices->isEmpty()) {
            return self::ok('No notices found for the current academic year.');
        }

        $lines = $notices->map(function (NoticeBoard $n) {
            $scope = $n->type.($n->standardLink_id ? " class#{$n->standardLink_id}" : '');

            return "- #{$n->id} [{$scope}] {$n->title} (status={$n->status})";
        })->implode("\n");

        return self::ok("**Notices (NoticeBoard / notice_board):**\n{$lines}", $notices->toArray());
    }

    /**
     * @param  array{title: string, description: string, type?: string, publish_date?: string, expire_date?: string, standardLink_id?: int|null}  $args
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function createNotice(User $user, array $args): array
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

        $type = $args['type'] ?? 'school';
        $validator = Validator::make([
            'title' => $args['title'] ?? null,
            'description' => $args['description'] ?? null,
            'type' => $type,
            'publish_date' => $args['publish_date'] ?? now()->format('Y-m-d'),
            'expire_date' => $args['expire_date'] ?? now()->addWeek()->format('Y-m-d'),
            'standardLink_id' => $args['standardLink_id'] ?? null,
        ], [
            'title' => 'required|string|max:100',
            'description' => 'required|string|max:5000',
            'type' => 'required|in:school,class,teacher',
            'publish_date' => 'required|date',
            'expire_date' => 'required|date|after_or_equal:publish_date',
            'standardLink_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return self::fail($validator->errors()->first());
        }

        $data = $validator->validated();
        if ($data['type'] === 'class' && empty($data['standardLink_id'])) {
            return self::fail('standardLink_id is required for class notices.');
        }

        $notice = new NoticeBoard;
        $notice->school_id = $schoolId;
        $notice->academic_year_id = $year->id;
        $notice->type = $data['type'];
        $notice->title = $data['title'];
        $notice->description = $data['description'];
        $notice->publish_date = date('Y-m-d H:i:s', strtotime($data['publish_date']));
        $notice->expire_date = date('Y-m-d H:i:s', strtotime($data['expire_date']));
        $notice->standardLink_id = $data['type'] === 'class' ? (int) $data['standardLink_id'] : null;
        $notice->status = now()->greaterThan($notice->expire_date) ? 0 : 1;
        $notice->save();

        return self::ok("Notice #{$notice->id} created: {$notice->title}", ['id' => $notice->id]);
    }

    /**
     * @param  array{notice_id: int, title?: string, description?: string, expire_date?: string, status?: int}  $args
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function updateNotice(User $user, array $args): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $notice = NoticeBoard::where('id', (int) ($args['notice_id'] ?? 0))
            ->where('school_id', $schoolId)
            ->first();

        if (! $notice) {
            return self::fail('Notice not found in your school.');
        }

        if (isset($args['title']) && is_string($args['title']) && $args['title'] !== '') {
            $notice->title = $args['title'];
        }
        if (isset($args['description']) && is_string($args['description']) && $args['description'] !== '') {
            $notice->description = $args['description'];
        }
        if (isset($args['expire_date']) && is_string($args['expire_date']) && $args['expire_date'] !== '') {
            $notice->expire_date = date('Y-m-d H:i:s', strtotime($args['expire_date']));
            $notice->status = now()->greaterThan($notice->expire_date) ? 0 : 1;
        }
        if (array_key_exists('status', $args) && $args['status'] !== null) {
            $notice->status = (int) $args['status'] ? 1 : 0;
        }

        $notice->save();

        return self::ok("Notice #{$notice->id} updated.", ['id' => $notice->id]);
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function listEvents(User $user, int $limit = 20): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $events = Events::where('school_id', $schoolId)
            ->where('category', '!=', 'holidays')
            ->orderByDesc('id')
            ->limit(max(1, min($limit, 50)))
            ->get(['id', 'title', 'category', 'select_type', 'start_date', 'end_date']);

        if ($events->isEmpty()) {
            return self::ok('No events found (excluding holidays).');
        }

        $lines = $events->map(fn (Events $e) => "- #{$e->id} [{$e->category}/{$e->select_type}] {$e->title} ({$e->start_date})")
            ->implode("\n");

        return self::ok("**Events:**\n{$lines}", $events->toArray());
    }

    /**
     * @param  array{title: string, description?: string, start_date: string, end_date: string, category?: string, select_type?: string, standard_id?: int|null}  $args
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function createEvent(User $user, array $args): array
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

        $category = $args['category'] ?? 'meeting';
        if ($category === 'holidays') {
            return self::fail('Use create holiday for category=holidays.');
        }

        $validator = Validator::make([
            'title' => $args['title'] ?? null,
            'description' => $args['description'] ?? '',
            'start_date' => $args['start_date'] ?? null,
            'end_date' => $args['end_date'] ?? null,
            'category' => $category,
            'select_type' => $args['select_type'] ?? 'school',
            'standard_id' => $args['standard_id'] ?? null,
        ], [
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:5000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'category' => 'required|in:culturals,education,exam,meeting',
            'select_type' => 'required|in:school,class,alumni',
            'standard_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return self::fail($validator->errors()->first());
        }

        $data = $validator->validated();
        if ($data['select_type'] === 'class' && empty($data['standard_id'])) {
            return self::fail('standard_id is required for class events.');
        }

        $event = new Events;
        $event->school_id = $schoolId;
        $event->academic_year_id = $year->id;
        $event->title = $data['title'];
        $event->description = $data['description'] ?? '';
        $event->category = $data['category'];
        $event->select_type = $data['select_type'];
        $event->standard_id = $data['select_type'] === 'class' ? (int) $data['standard_id'] : null;
        $event->batch = '';
        $event->start_date = date('Y-m-d H:i:s', strtotime($data['start_date']));
        $event->end_date = date('Y-m-d H:i:s', strtotime($data['end_date']));
        $event->created_by = $user->id;
        $event->status = 'active';
        $event->color = $data['select_type'] === 'class' ? 'blue' : 'green';
        $event->save();

        if (! Gate::forUser($user)->allows('event', $event)) {
            $event->delete();

            return self::fail('Event failed school authorization after create.');
        }

        return self::ok("Event #{$event->id} created: {$event->title}", ['id' => $event->id]);
    }

    /**
     * @param  array{event_id: int, title?: string, description?: string, start_date?: string, end_date?: string}  $args
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function updateEvent(User $user, array $args): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $event = Events::where('id', (int) ($args['event_id'] ?? 0))
            ->where('category', '!=', 'holidays')
            ->first();

        if (! $event) {
            return self::fail('Event not found.');
        }

        // Post-#131 school_id Gate — ug3 cannot touch another school's event.
        if (! Gate::forUser($user)->allows('event', $event)) {
            return self::fail('You are not authorized to update this event.');
        }

        if ((int) $event->school_id !== (int) $schoolId) {
            return self::fail('Event does not belong to your school.');
        }

        if (isset($args['title']) && is_string($args['title']) && $args['title'] !== '') {
            $event->title = $args['title'];
        }
        if (isset($args['description']) && is_string($args['description'])) {
            $event->description = $args['description'];
        }
        if (isset($args['start_date']) && is_string($args['start_date']) && $args['start_date'] !== '') {
            $event->start_date = date('Y-m-d H:i:s', strtotime($args['start_date']));
        }
        if (isset($args['end_date']) && is_string($args['end_date']) && $args['end_date'] !== '') {
            $event->end_date = date('Y-m-d H:i:s', strtotime($args['end_date']));
        }

        $event->updated_by = $user->id;
        $event->save();

        return self::ok("Event #{$event->id} updated.", ['id' => $event->id]);
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function listHolidays(User $user, int $limit = 20): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $holidays = Events::where('school_id', $schoolId)
            ->where('category', 'holidays')
            ->orderBy('start_date')
            ->limit(max(1, min($limit, 50)))
            ->get(['id', 'title', 'start_date', 'end_date']);

        if ($holidays->isEmpty()) {
            return self::ok('No holidays found.');
        }

        $lines = $holidays->map(fn (Events $h) => "- #{$h->id} {$h->title} ({$h->start_date})")
            ->implode("\n");

        return self::ok("**Holidays (Events.category=holidays):**\n{$lines}", $holidays->toArray());
    }

    /**
     * @param  array{title: string, date: string}  $args
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function createHoliday(User $user, array $args): array
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

        $validator = Validator::make([
            'title' => $args['title'] ?? null,
            'date' => $args['date'] ?? null,
        ], [
            'title' => 'required|string|max:150',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return self::fail($validator->errors()->first());
        }

        $data = $validator->validated();
        $day = date('Y-m-d', strtotime($data['date']));

        $holiday = new Events;
        $holiday->school_id = $schoolId;
        $holiday->academic_year_id = $year->id;
        $holiday->title = $data['title'];
        $holiday->category = 'holidays';
        $holiday->select_type = 'school';
        $holiday->batch = '';
        $holiday->start_date = $day.' 00:00:00';
        $holiday->end_date = $day.' 23:59:59';
        $holiday->created_by = $user->id;
        $holiday->status = 'active';
        $holiday->color = 'red';
        $holiday->save();

        if (! Gate::forUser($user)->allows('event', $holiday)) {
            $holiday->delete();

            return self::fail('Holiday failed school authorization after create.');
        }

        return self::ok("Holiday #{$holiday->id} created: {$holiday->title}", ['id' => $holiday->id]);
    }

    /**
     * @param  array{holiday_id: int, title?: string, date?: string}  $args
     * @return array{success: bool, message: string, data?: mixed}
     */
    public static function updateHoliday(User $user, array $args): array
    {
        $user = ToshiActionService::getEffectiveUser($user);
        $schoolId = ToshiActionService::getEffectiveSchoolId($user);
        if (! $schoolId) {
            return self::fail('No school assigned.');
        }

        $holiday = Events::where('id', (int) ($args['holiday_id'] ?? 0))
            ->where('category', 'holidays')
            ->first();

        if (! $holiday) {
            return self::fail('Holiday not found.');
        }

        if (! Gate::forUser($user)->allows('event', $holiday)) {
            return self::fail('You are not authorized to update this holiday.');
        }

        if ((int) $holiday->school_id !== (int) $schoolId) {
            return self::fail('Holiday does not belong to your school.');
        }

        if (isset($args['title']) && is_string($args['title']) && $args['title'] !== '') {
            $holiday->title = $args['title'];
        }
        if (isset($args['date']) && is_string($args['date']) && $args['date'] !== '') {
            $day = date('Y-m-d', strtotime($args['date']));
            $holiday->start_date = $day.' 00:00:00';
            $holiday->end_date = $day.' 23:59:59';
        }

        $holiday->updated_by = $user->id;
        $holiday->save();

        return self::ok("Holiday #{$holiday->id} updated.", ['id' => $holiday->id]);
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
