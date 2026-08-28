<?php

namespace App\Services\Toshi;

use App\Helpers\SiteHelper;
use App\Models\Academics\Exam;
use App\Models\Attendance;
use App\Models\Events;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\StudentAcademic;
use App\Models\StudentHealthIncident;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Services\OutboundWhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Parent-scoped (ug7) read helpers for WhatsApp / ParentOperationsAgent.
 *
 * Ownership: children are resolved only from student_parent_links for the
 * authenticated parent — never from an LLM-supplied student_id.
 */
class ParentActionService
{
    /**
     * @return Collection<int, User>
     */
    public static function linkedStudents(User $parent): Collection
    {
        return StudentParentLink::query()
            ->where('parent_id', $parent->id)
            ->where('status', 1)
            ->with(['userStudent.studentAcademicLatest.standardLink.standard'])
            ->get()
            ->map(fn (StudentParentLink $link) => $link->userStudent)
            ->filter()
            ->values();
    }

    /**
     * Resolve a child by optional name / ordinal against linked students only.
     * Rejects any explicit student_id that is not in the linked set.
     *
     * @return array{ok: bool, student?: User, message?: string, students?: Collection<int, User>}
     */
    public static function resolveChild(User $parent, ?string $childName = null, ?int $studentId = null): array
    {
        $students = self::linkedStudents($parent);

        if ($students->isEmpty()) {
            return [
                'ok' => false,
                'message' => 'No children are linked to your WhatsApp account. Reply with a School Pay code or contact the school office to link a student.',
                'students' => $students,
            ];
        }

        if ($studentId !== null) {
            $match = $students->firstWhere('id', $studentId);
            if (! $match) {
                return [
                    'ok' => false,
                    'message' => 'That student is not linked to your account. I can only show data for your linked children.',
                    'students' => $students,
                ];
            }

            return ['ok' => true, 'student' => $match, 'students' => $students];
        }

        if ($childName !== null && trim($childName) !== '') {
            $needle = mb_strtolower(trim($childName));

            if (ctype_digit($needle)) {
                $ordinal = (int) $needle;
                if ($ordinal >= 1 && $ordinal <= $students->count()) {
                    return ['ok' => true, 'student' => $students[$ordinal - 1], 'students' => $students];
                }
            }

            $matches = $students->filter(function (User $student) use ($needle) {
                return str_contains(mb_strtolower((string) $student->name), $needle);
            })->values();

            if ($matches->count() === 1) {
                return ['ok' => true, 'student' => $matches->first(), 'students' => $students];
            }

            if ($matches->isEmpty()) {
                $names = $students->map(fn (User $s) => $s->name)->implode(', ');

                return [
                    'ok' => false,
                    'message' => "No linked child matched \"{$childName}\". Your children: {$names}.",
                    'students' => $students,
                ];
            }

            $names = $matches->map(fn (User $s) => $s->name)->implode(', ');

            return [
                'ok' => false,
                'message' => "Several children matched \"{$childName}\" ({$names}). Please be more specific.",
                'students' => $students,
            ];
        }

        if ($students->count() === 1) {
            return ['ok' => true, 'student' => $students->first(), 'students' => $students];
        }

        $names = $students->values()->map(fn (User $s, int $i) => ($i + 1).'. '.$s->name)->implode("\n");

        return [
            'ok' => false,
            'message' => "You have more than one linked child. Specify which one:\n{$names}",
            'students' => $students,
        ];
    }

    public static function listChildren(User $parent): array
    {
        $students = self::linkedStudents($parent);

        if ($students->isEmpty()) {
            return self::result(false, 'No children linked to your account.');
        }

        $lines = $students->values()->map(function (User $student, int $i) {
            $class = $student->studentAcademicLatest?->standardLink?->StandardSection ?? 'N/A';

            return ($i + 1).'. '.$student->name.' — '.$class;
        })->implode("\n");

        return self::result(true, "**Your children:**\n{$lines}", [
            'count' => $students->count(),
            'ids' => $students->pluck('id')->all(),
        ]);
    }

    public static function feeBalance(User $parent, ?string $childName = null): array
    {
        $resolved = self::resolveChild($parent, $childName);
        if (! $resolved['ok']) {
            return self::result(false, $resolved['message']);
        }

        /** @var User $student */
        $student = $resolved['student'];
        $schoolId = self::schoolIdForLinkedChild($parent, $student);
        if ($schoolId === null) {
            return self::result(false, 'Could not determine the school for this child. Please contact the school office.');
        }

        $academic = StudentAcademic::where('school_id', $schoolId)
            ->where('user_id', $student->id)
            ->first();

        $standardId = $academic?->standardLink?->standard_id
            ?? $student->studentAcademicLatest?->standardLink?->standard_id;

        $categories = collect();
        if ($standardId) {
            $categories = FeesCategories::where('school_id', $schoolId)
                ->where('standard_id', $standardId)
                ->get();
        }

        $totalFees = (float) $categories->sum('amount');
        $totalPaid = (float) FeePayment::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->sum('amount');
        $totalBalance = max(0, $totalFees - $totalPaid);

        $categoryRows = $categories->map(fn ($cat) => [
            'name' => $cat->name,
            'amount' => (float) $cat->amount,
            'balance' => max(0, (float) $cat->amount), // category-level paid split not available; show amount due
        ])->all();

        // Prefer paid vs outstanding when we only have aggregate paid:
        // mark all as outstanding when balance > 0, else paid.
        if ($totalBalance <= 0 && $categoryRows !== []) {
            $categoryRows = array_map(function (array $row) {
                $row['balance'] = 0;

                return $row;
            }, $categoryRows);
        }

        $message = app(OutboundWhatsAppService::class)->composeFeeBalance(
            self::studentForCompose($student),
            $categoryRows,
            $totalPaid,
            $totalBalance,
        );

        return self::result(true, $message, [
            'student_id' => $student->id,
            'total_paid' => $totalPaid,
            'total_balance' => $totalBalance,
        ]);
    }

    public static function attendance(User $parent, ?string $childName = null): array
    {
        $resolved = self::resolveChild($parent, $childName);
        if (! $resolved['ok']) {
            return self::result(false, $resolved['message']);
        }

        /** @var User $student */
        $student = $resolved['student'];
        $records = Attendance::where('user_id', $student->id)
            ->where('date', '>=', Carbon::now()->subMonth())
            ->orderByDesc('date')
            ->get();

        $present = $records->where('status', 1)->count();
        $absent = $records->where('status', 0)->count();
        $total = $records->count();
        $recentAbsences = $records->where('status', '!=', 1)->take(5)->map(fn ($r) => [
            'date' => Carbon::parse($r->date)->format('d M Y'),
            'reason' => (int) $r->status === 2 ? 'Late' : 'Absent',
        ])->values()->all();

        $message = app(OutboundWhatsAppService::class)->composeAttendance(
            self::studentForCompose($student),
            $present,
            $absent,
            $total,
            $recentAbsences,
        );

        return self::result(true, $message, [
            'student_id' => $student->id,
            'present' => $present,
            'absent' => $absent,
            'total' => $total,
        ]);
    }

    public static function grades(User $parent, ?string $childName = null): array
    {
        $resolved = self::resolveChild($parent, $childName);
        if (! $resolved['ok']) {
            return self::result(false, $resolved['message']);
        }

        /** @var User $student */
        $student = $resolved['student'];
        $schoolId = self::schoolIdForLinkedChild($parent, $student);
        if ($schoolId === null) {
            return self::result(false, 'Could not determine the school for this child. Please contact the school office.');
        }

        $academicYear = SiteHelper::getAcademicYear($schoolId);
        $examQuery = Exam::whereHas('marks', fn ($q) => $q->where('student_id', $student->id))
            ->where('school_id', $schoolId)
            ->with([
                'marks' => fn ($q) => $q->where('student_id', $student->id)->with('subject'),
                'examType',
                'subject',
            ])
            ->latest('id');

        if ($academicYear) {
            $examQuery->where('academic_year_id', $academicYear->id);
        }

        $exams = $examQuery->take(15)->get();
        if ($exams->isEmpty()) {
            return self::result(true, "No results published yet for {$student->name}.");
        }

        $composer = app(OutboundWhatsAppService::class);
        $parts = [];

        foreach ($exams->groupBy(fn ($e) => $e->examType?->name ?? 'Exam') as $typeName => $typeExams) {
            $subjects = [];
            foreach ($typeExams as $exam) {
                foreach ($exam->marks as $mark) {
                    $subjects[] = [
                        'name' => $mark->subject?->name ?? $exam->subject?->name ?? 'Subject',
                        'score' => (float) ($mark->marks ?? 0),
                        'total' => 100,
                        'grade' => $mark->grade ?? '-',
                    ];
                }
            }

            $parts[] = $composer->composeGradesOverview(
                self::studentForCompose($student),
                (string) $typeName,
                $subjects,
            );
        }

        return self::result(true, implode("\n\n", $parts), [
            'student_id' => $student->id,
            'exam_groups' => count($parts),
        ]);
    }

    public static function health(User $parent, ?string $childName = null): array
    {
        $resolved = self::resolveChild($parent, $childName);
        if (! $resolved['ok']) {
            return self::result(false, $resolved['message']);
        }

        /** @var User $student */
        $student = $resolved['student'];
        $schoolId = self::schoolIdForLinkedChild($parent, $student);
        if ($schoolId === null) {
            return self::result(false, 'Could not determine the school for this child. Please contact the school office.');
        }

        $incidents = StudentHealthIncident::whereSchool($schoolId)
            ->where('user_id', $student->id)
            ->orderByDesc('incident_date')
            ->limit(10)
            ->get();

        if ($incidents->isEmpty()) {
            return self::result(true, "No health records found for {$student->name}.");
        }

        $records = $incidents->map(fn (StudentHealthIncident $incident) => [
            'date' => optional($incident->incident_date)->format('d M Y'),
            'type' => ucfirst((string) ($incident->severity ?? 'checkup')),
            'notes' => trim(($incident->description ?? '').($incident->action_taken ? ' — '.$incident->action_taken : '')),
        ])->all();

        $message = app(OutboundWhatsAppService::class)->composeHealthRecord(
            self::studentForCompose($student),
            $records,
        );

        return self::result(true, $message, [
            'student_id' => $student->id,
            'count' => count($records),
        ]);
    }

    public static function events(User $parent): array
    {
        $query = Events::where('school_id', $parent->school_id)
            ->where('category', '!=', 'holidays')
            ->orderByDesc('id')
            ->limit(10);

        $academicYear = SiteHelper::getAcademicYear($parent->school_id);
        if ($academicYear) {
            $query->where('academic_year_id', $academicYear->id);
        }

        $events = $query->get(['id', 'title', 'start_date']);
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
     * Tenant scope for a linked child: prefer student_parent_links.school_id, then the student's school.
     */
    private static function schoolIdForLinkedChild(User $parent, User $student): ?int
    {
        $link = StudentParentLink::query()
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->where('status', 1)
            ->first();

        if ($link?->school_id) {
            return (int) $link->school_id;
        }

        if ($student->school_id) {
            return (int) $student->school_id;
        }

        return $parent->school_id ? (int) $parent->school_id : null;
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
