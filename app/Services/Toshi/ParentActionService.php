<?php

namespace App\Services\Toshi;

use App\Models\Events;
use App\Models\User;
use App\Services\OutboundWhatsAppService;
use App\Services\Parent\ParentPortalService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Parent-scoped (ug7) read helpers for WhatsApp / ParentOperationsAgent.
 *
 * Data queries delegate to ParentPortalService — single source for web + WhatsApp.
 */
class ParentActionService
{
    private static function portal(): ParentPortalService
    {
        return app(ParentPortalService::class);
    }

    /**
     * @return Collection<int, User>
     */
    public static function linkedStudents(User $parent): Collection
    {
        return self::portal()->linkedStudents($parent);
    }

    /**
     * @return array{ok: bool, student?: User, message?: string, students?: Collection<int, User>}
     */
    public static function resolveChild(User $parent, ?string $childName = null, ?int $studentId = null): array
    {
        return self::portal()->resolveChild($parent, $childName, $studentId);
    }

    public static function listChildren(User $parent): array
    {
        $result = self::portal()->listChildren($parent);

        if (! $result['success']) {
            return self::result(false, $result['message'] ?? 'No children linked.');
        }

        $lines = collect($result['children'])->map(function (array $child) {
            return $child['ordinal'].'. '.$child['name'].' — '.$child['class'];
        })->implode("\n");

        return self::result(true, "**Your children:**\n{$lines}", [
            'count' => $result['count'],
            'ids' => collect($result['children'])->pluck('student_id')->all(),
        ]);
    }

    public static function feeBalance(User $parent, ?string $childName = null): array
    {
        $resolved = self::resolveChild($parent, $childName);
        if (! $resolved['ok']) {
            return self::result(false, $resolved['message']);
        }

        $payload = self::portal()->feeBalance($parent, $childName);
        if (! $payload['success']) {
            return self::result(false, $payload['message'] ?? 'Unable to load fee balance.');
        }

        /** @var User $student */
        $student = $resolved['student'];
        $data = $payload['data'];

        $message = app(OutboundWhatsAppService::class)->composeFeeBalance(
            self::studentForCompose($student),
            $data['categories'],
            $data['total_paid'],
            $data['total_balance'],
        );

        return self::result(true, $message, [
            'student_id' => $data['student_id'],
            'total_paid' => $data['total_paid'],
            'total_balance' => $data['total_balance'],
        ]);
    }

    public static function attendance(User $parent, ?string $childName = null): array
    {
        $resolved = self::resolveChild($parent, $childName);
        if (! $resolved['ok']) {
            return self::result(false, $resolved['message']);
        }

        $payload = self::portal()->attendance($parent, $childName);
        if (! $payload['success']) {
            return self::result(false, $payload['message'] ?? 'Unable to load attendance.');
        }

        /** @var User $student */
        $student = $resolved['student'];
        $data = $payload['data'];

        $message = app(OutboundWhatsAppService::class)->composeAttendance(
            self::studentForCompose($student),
            $data['present'],
            $data['absent'],
            $data['total'],
            $data['recent_absences'],
        );

        return self::result(true, $message, [
            'student_id' => $data['student_id'],
            'present' => $data['present'],
            'absent' => $data['absent'],
            'total' => $data['total'],
        ]);
    }

    public static function grades(User $parent, ?string $childName = null): array
    {
        $resolved = self::resolveChild($parent, $childName);
        if (! $resolved['ok']) {
            return self::result(false, $resolved['message']);
        }

        $payload = self::portal()->grades($parent, $childName);
        if (! $payload['success']) {
            return self::result(false, $payload['message'] ?? 'Unable to load grades.');
        }

        /** @var User $student */
        $student = $resolved['student'];
        $data = $payload['data'];

        if ($data['exam_groups'] === []) {
            return self::result(true, $payload['message'] ?? "No results published yet for {$student->name}.");
        }

        $composer = app(OutboundWhatsAppService::class);
        $parts = [];

        foreach ($data['exam_groups'] as $group) {
            $parts[] = $composer->composeGradesOverview(
                self::studentForCompose($student),
                $group['exam_type'],
                $group['subjects'],
            );
        }

        return self::result(true, implode("\n\n", $parts), [
            'student_id' => $data['student_id'],
            'exam_groups' => count($parts),
        ]);
    }

    public static function health(User $parent, ?string $childName = null): array
    {
        $resolved = self::resolveChild($parent, $childName);
        if (! $resolved['ok']) {
            return self::result(false, $resolved['message']);
        }

        $payload = self::portal()->health($parent, $childName);
        if (! $payload['success']) {
            return self::result(false, $payload['message'] ?? 'Unable to load health records.');
        }

        /** @var User $student */
        $student = $resolved['student'];
        $data = $payload['data'];

        if ($data['count'] === 0) {
            return self::result(true, $payload['message'] ?? "No health records found for {$student->name}.");
        }

        $message = app(OutboundWhatsAppService::class)->composeHealthRecord(
            self::studentForCompose($student),
            $data['records'],
        );

        return self::result(true, $message, [
            'student_id' => $data['student_id'],
            'count' => $data['count'],
        ]);
    }

    public static function events(User $parent): array
    {
        $schoolIds = self::linkedStudents($parent)
            ->map(fn (User $student) => self::portal()->schoolIdForLinkedChild($parent, $student))
            ->filter()
            ->unique()
            ->values();

        if ($schoolIds->isEmpty()) {
            return self::result(true, 'No upcoming school events found.');
        }

        $events = collect();
        foreach ($schoolIds as $schoolId) {
            $query = Events::where('school_id', $schoolId)
                ->where('category', '!=', 'holidays')
                ->orderByDesc('id')
                ->limit(10);

            $academicYear = \App\Helpers\SiteHelper::getAcademicYear($schoolId);
            if ($academicYear) {
                $query->where('academic_year_id', $academicYear->id);
            }

            $events = $events->merge($query->get(['id', 'title', 'start_date', 'school_id']));
        }

        $events = $events->sortByDesc('id')->take(10)->values();

        if ($events->isEmpty()) {
            return self::result(true, 'No upcoming school events found.');
        }

        $lines = $events->map(function ($event) {
            $date = $event->start_date
                ? Carbon::parse($event->start_date)->format('d M Y')
                : 'TBA';

            return "• {$event->title} — {$date}";
        })->implode("\n");

        return self::result(true, "**School events:**\n{$lines}", [
            'count' => $events->count(),
        ]);
    }

    /**
     * compose* helpers expect a singular studentAcademic relation; User often
     * exposes studentAcademic as a hasMany collection — normalize for formatting.
     */
    private static function studentForCompose(User $student): User
    {
        $clone = clone $student;
        $academic = $student->relationLoaded('studentAcademicLatest')
            ? $student->studentAcademicLatest
            : $student->studentAcademicLatest()->first();

        $clone->setRelation('studentAcademic', $academic);

        return $clone;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, data: array<string, mixed>}
     */
    private static function result(bool $success, string $message, array $data = []): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ];
    }
}
