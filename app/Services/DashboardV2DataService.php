<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;
use App\Models\Academics\Exam as ExamModel;
use App\Models\Academics\Marks;
use App\Models\Attendance;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserPreference;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data for the PR1 admin first screen (school-with-data dashboard, v2 flag).
 *
 * Contract:
 *  - ONE step source: OnboardingStepsService (progress + neutral labels).
 *  - Money strings use the school's currency (school_details meta `currency`,
 *    default UGX) and the compact KlassApp style ("UGX 30.5M").
 *  - Everything is school-scoped and counts active accounts only.
 *  - No fabricated data: empty sources produce empty/​mid states, never placeholders.
 */
class DashboardV2DataService
{
    public function build(School $school, User $user): array
    {
        $sid = (int) $school->id;

        $year = SiteHelper::getAcademicYear($sid);
        $term = $this->currentTerm($sid, $year?->id);

        $students = $this->studentsCount($sid);

        return [
            'schoolName' => (string) $school->name,
            'termLabel' => $this->termLabel($term, $year),
            'state' => $students === 0 ? 'new' : 'data',
            'setup' => $this->setup($school, $user),
            'kpis' => $this->kpis($sid, $students, $term),
            'charts' => $this->charts($sid, $students, $term),
            'activity' => $this->activity($sid),
            'currency' => $this->currencyFor($sid),
        ];
    }

    // ─────────────────────────────── shared ────────────────────────────────

    private function currentTerm(int $sid, ?int $yearId): ?AcademicTerm
    {
        if (! $yearId) {
            return null;
        }

        return AcademicTerm::query()
            ->where('school_id', $sid)
            ->where('academic_year_id', $yearId)
            ->orderByRaw("CASE WHEN status = 'current' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->first();
    }

    private function termLabel(?AcademicTerm $term, ?object $year): string
    {
        $parts = array_filter([
            $term?->name,
            $year?->name,
        ]);

        return $parts === [] ? '' : implode(', ', $parts);
    }

    private function studentsCount(int $sid): int
    {
        return User::query()
            ->where('school_id', $sid)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();
    }

    public function currencyFor(int $sid): string
    {
        $meta = DB::table('school_details')
            ->where('school_id', $sid)
            ->where('meta_key', 'currency')
            ->value('meta_value');

        $code = is_string($meta) && trim($meta) !== '' && trim($meta) !== '-' ? trim($meta) : 'UGX';

        return $code;
    }

    public function compactMoney(float|int|null $amount): string
    {
        $amount = (float) $amount;
        if ($amount >= 1_000_000) {
            return rtrim(rtrim(number_format($amount / 1_000_000, 1, '.', ''), '0'), '.').'M';
        }
        if ($amount >= 1_000) {
            return rtrim(rtrim(number_format($amount / 1_000, 1, '.', ''), '0'), '.').'K';
        }

        return number_format($amount, 0);
    }

    // ─────────────────────────────── setup ─────────────────────────────────

    private function setup(School $school, User $user): array
    {
        $progress = OnboardingStepsService::progress($school, (int) $user->id);
        $steps = collect(OnboardingStepsService::steps($school, (int) $user->id))->keyBy('key');

        $label = function (string $key) use ($steps, $school): string {
            $step = $steps->get($key) ?? ['label' => ucfirst($key)];

            return OnboardingStepsService::displayLabel($key, $step, $school);
        };

        $labels = [];
        foreach (array_keys($progress['labels']) as $key) {
            $labels[$key] = $label((string) $key);
        }

        $next = null;
        if ($progress['next'] !== null) {
            $step = $steps->get($progress['next']['key']);
            $next = [
                'key' => $progress['next']['key'],
                'label' => $label((string) $progress['next']['key']),
                'route' => $step['route'] ?? null,
            ];
        }

        $routes = [];
        foreach ($steps as $key => $step) {
            $routes[(string) $key] = $step['route'] ?? null;
        }

        return [
            'total' => $progress['total'],
            'done' => $progress['done'],
            'percent' => $progress['percent'],
            'next' => $next,
            'labels' => $labels,
            'incomplete' => $progress['incomplete'],
            'routes' => $routes,
            'dismissed' => UserPreference::get(
                $user,
                UserPreference::setupBannerDismissedKey((int) $school->id),
            ) !== null,
        ];
    }

    // ─────────────────────────────── KPIs ──────────────────────────────────

    private function kpis(int $sid, int $students, ?AcademicTerm $term): array
    {
        // Staff
        $teachers = User::query()
            ->where('school_id', $sid)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();
        $admins = User::query()
            ->where('school_id', $sid)
            ->whereIn('usergroup_id', [3, 4])
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();

        // Students joined this term
        $joined = null;
        if ($term?->starts_on) {
            $joined = User::query()
                ->where('school_id', $sid)
                ->where('usergroup_id', 6)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->where('created_at', '>=', $term->starts_on->startOfDay())
                ->count();
        }

        // Attendance this week vs last week
        $weekStart = now()->startOfWeek();
        $thisWeek = $this->attendanceRate($sid, $weekStart, now());
        $lastStart = now()->subWeek()->startOfWeek();
        $lastEnd = now()->subWeek()->endOfWeek();
        $lastWeek = $this->attendanceRate($sid, $lastStart, $lastEnd);

        $delta = ($thisWeek !== null && $lastWeek !== null)
            ? round($thisWeek - $lastWeek, 1)
            : null;

        // Fees — same numbers as the payments surfaces (FeePositionService).
        $feePosition = app(FeePositionService::class)->forSchool($sid);
        $hasStructure = FeesCategories::query()->where('school_id', $sid)->exists();
        $currency = $this->currencyFor($sid);

        // Report cards: students with marks for the latest exam.
        $exam = ExamModel::query()
            ->where('school_id', $sid)
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->first();
        $ready = $exam
            ? Marks::query()->where('school_id', $sid)->where('exam_id', $exam->id)
                ->whereNull('deleted_at')->distinct()->count('student_id')
            : 0;

        return [
            'students' => [
                'value' => $students,
                'detail' => $joined === null
                    ? '—'
                    : ($joined > 0 ? '+'.$joined.' this term' : 'No change this term'),
                'direction' => ($joined !== null && $joined > 0) ? 'up' : null,
            ],
            'staff' => [
                'value' => $teachers + $admins,
                'detail' => $teachers.' teachers · '.$admins.' admin',
            ],
            'attendance' => [
                'value' => $thisWeek !== null ? number_format($thisWeek, 1).'%' : '–',
                'detail' => $delta === null
                    ? ($thisWeek === null ? 'No register taken yet' : '—')
                    : $this->deltaLabel($delta),
                'direction' => $delta === null ? null : ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat')),
            ],
            'fees' => [
                'percent' => $hasStructure ? (int) $feePosition['rate'] : null,
                'collected' => $feePosition['collected_raw'],
                'expected' => $feePosition['expected_raw'],
                'collected_label' => $currency.' '.$this->compactMoney($feePosition['collected_raw']),
                'expected_label' => $currency.' '.$this->compactMoney($feePosition['expected_raw']),
                'state' => $hasStructure ? 'ok' : 'no_structure',
            ],
            'report_cards' => [
                'ready' => $ready,
                'students' => $students,
                'exam' => $this->examLabel($exam),
                'percent' => ($exam && $students > 0) ? (int) round($ready / $students * 100) : null,
                'state' => $exam ? 'ok' : 'no_exam',
            ],
        ];
    }

    private function examLabel(?ExamModel $exam): ?string
    {
        if (! $exam) {
            return null;
        }

        return $exam->examType?->name
            ?? $exam->subject?->name
            ?? 'Latest exam';
    }

    private function deltaLabel(float $delta): string
    {
        if ($delta == 0.0) {
            return 'No change on last week';
        }

        $arrow = $delta > 0 ? '▲' : '▼';

        return $arrow.' '.number_format(abs($delta), 1).' pts on last week';
    }

    /**
     * Percentage of present rows in [$start, $end]; null when no register exists.
     */
    private function attendanceRate(int $sid, Carbon $start, Carbon $end): ?float
    {
        $rows = Attendance::query()
            ->where('school_id', $sid)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present')
            ->first();

        $total = (int) ($rows->total ?? 0);
        if ($total === 0) {
            return null;
        }

        return round(((int) $rows->present) / $total * 100, 1);
    }

    // ────────────────────────────── charts ─────────────────────────────────

    private function charts(int $sid, int $students, ?AcademicTerm $term): array
    {
        // Performance by class: latest exam, average mark per section.
        $exam = ExamModel::query()
            ->where('school_id', $sid)
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->first();

        $perClass = [];
        if ($exam) {
            $rows = Marks::query()
                ->where('school_id', $sid)
                ->where('exam_id', $exam->id)
                ->whereNull('deleted_at')
                ->select('section_id', DB::raw('AVG(marks) as avg_mark'))
                ->groupBy('section_id')
                ->get();
            $names = Section::query()->whereIn('id', $rows->pluck('section_id'))->pluck('name', 'id');
            foreach ($rows as $row) {
                $perClass[] = [
                    'label' => (string) ($names[$row->section_id] ?? 'Class'),
                    'value' => round((float) $row->avg_mark, 1),
                ];
            }
            usort($perClass, fn ($a, $b) => $b['value'] <=> $a['value']);
        }

        // Attendance trend: last 8 weeks.
        $weeks = [];
        for ($i = 7; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = $i === 0 ? now() : now()->subWeeks($i)->endOfWeek();
            $rate = $this->attendanceRate($sid, $start, $end);
            $weeks[] = [
                'label' => 'W'.(8 - $i),
                'value' => $rate,
            ];
        }

        // Gender split (missing gender counts as Not specified).
        $genderRow = DB::table('users')
            ->join('userprofiles', 'userprofiles.user_id', '=', 'users.id')
            ->where('users.school_id', $sid)
            ->where('users.usergroup_id', 6)
            ->where('users.status', 'active')
            ->whereNull('users.deleted_at')
            ->selectRaw("SUM(CASE WHEN userprofiles.gender = 'female' THEN 1 ELSE 0 END) as girls")
            ->selectRaw("SUM(CASE WHEN userprofiles.gender = 'male' THEN 1 ELSE 0 END) as boys")
            ->selectRaw("SUM(CASE WHEN userprofiles.gender NOT IN ('female', 'male') OR userprofiles.gender IS NULL THEN 1 ELSE 0 END) as not_specified")
            ->first();

        // Fees by month: collected vs an equal monthly share of the term's expected.
        $feePosition = app(FeePositionService::class)->forSchool($sid);
        $months = [];
        if ($term?->starts_on && $term?->ends_on && $feePosition['expected_raw'] > 0) {
            $cursor = $term->starts_on->copy()->startOfMonth();
            $last = $term->ends_on->copy()->startOfMonth();
            $monthCount = max(1, $cursor->diffInMonths($last) + 1);
            $share = $feePosition['expected_raw'] / $monthCount;
            $all = [];
            while ($cursor <= $last) {
                $collected = (float) FeePayment::query()
                    ->where('school_id', $sid)
                    ->whereDate('paid_on', '>=', $cursor->copy()->startOfMonth()->toDateString())
                    ->whereDate('paid_on', '<=', $cursor->copy()->endOfMonth()->toDateString())
                    ->sum('amount');
                $all[] = [
                    'label' => $cursor->format('M'),
                    'collected' => $collected,
                    'expected' => round($share, 2),
                ];
                $cursor->addMonth();
            }
            $months = array_slice($all, -4);
        }

        return [
            'per_class' => $perClass,
            'exam' => $this->examLabel($exam),
            'attendance_weeks' => $weeks,
            'gender' => [
                'girls' => (int) ($genderRow->girls ?? 0),
                'boys' => (int) ($genderRow->boys ?? 0),
                'not_specified' => (int) ($genderRow->not_specified ?? 0),
                'total' => $students,
            ],
            'fees_months' => $months,
        ];
    }

    // ───────────────────────────── activity ────────────────────────────────

    /**
     * Recent activity from real domain events: payments, attendance registers,
     * marks entry and student additions. No activity rows are fabricated.
     *
     * @return list<array{icon: string, at: string, ts: int, segments: list<string|array{bold: string}>}>
     */
    public function activity(int $sid): array
    {
        $events = [];

        $payment = FeePayment::query()
            ->where('school_id', $sid)
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->first();
        if ($payment) {
            $name = User::withTrashed()->find($payment->user_id)?->name ?? 'a student';
            $cur = $this->currencyFor($sid);
            $events[] = [
                'icon' => 'wallet',
                'ts' => Carbon::parse($payment->paid_on ?? $payment->created_at)->getTimestamp(),
                'ts_date' => Carbon::parse($payment->paid_on ?? $payment->created_at),
                'segments' => [
                    'Payment of '.$cur.' '.number_format((float) $payment->amount).' recorded for ',
                    ['bold' => $name],
                ],
            ];
        }

        $latest = Attendance::query()
            ->where('school_id', $sid)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();
        if ($latest) {
            $group = Attendance::query()
                ->where('school_id', $sid)
                ->whereDate('date', $latest->date)
                ->where('standardLink_id', $latest->standardLink_id)
                ->where('session', $latest->session)
                ->get();
            $present = $group->where('status', 1)->count();
            $total = $group->count();
            $recorder = User::withTrashed()->find($latest->recorded_by)?->name;
            $section = StandardLink::find($latest->standardLink_id)?->section?->name;
            if ($total > 0) {
                $events[] = [
                    'icon' => 'calendar-check',
                    'ts' => Carbon::parse($latest->created_at ?? $latest->date)->getTimestamp(),
                    'ts_date' => Carbon::parse($latest->created_at ?? $latest->date),
                    'segments' => [
                        ($recorder ? $recorder.' took' : 'Attendance taken').' attendance for ',
                        ['bold' => (string) ($section ?? 'a class')],
                        ' ('.$present.' of '.$total.' present)',
                    ],
                ];
            }
        }

        $mark = Marks::query()
            ->where('school_id', $sid)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->first();
        if ($mark) {
            $subject = Subject::find($mark->subject_id)?->name;
            $section = Section::find($mark->section_id)?->name;
            $events[] = [
                'icon' => 'clipboard-list',
                'ts' => Carbon::parse($mark->created_at)->getTimestamp(),
                'ts_date' => Carbon::parse($mark->created_at),
                'segments' => [
                    ($subject ? $subject.' marks' : 'Marks').' entered for ',
                    ['bold' => (string) ($section ?? 'a class')],
                ],
            ];
        }

        $student = User::query()
            ->where('school_id', $sid)
            ->where('usergroup_id', 6)
            ->orderByDesc('created_at')
            ->first();
        if ($student) {
            $sameDay = User::query()
                ->where('school_id', $sid)
                ->where('usergroup_id', 6)
                ->whereDate('created_at', $student->created_at->toDateString())
                ->count();
            $events[] = [
                'icon' => 'user-plus',
                'ts' => $student->created_at->getTimestamp(),
                'ts_date' => $student->created_at,
                'segments' => $sameDay > 1
                    ? [[ 'bold' => $sameDay.' students'], ' added']
                    : ['Student added: ', ['bold' => $student->name]],
            ];
        }

        usort($events, fn ($a, $b) => $b['ts'] <=> $a['ts']);
        $events = array_slice($events, 0, 5);

        return array_map(function (array $event): array {
            /** @var Carbon $at */
            $at = $event['ts_date'];
            $time = $at->isToday() ? $at->format('H:i') : ($at->isYesterday() ? 'Yesterday' : $at->format('D'));
            unset($event['ts_date'], $event['ts']);

            return [...$event, 'time' => $time];
        }, $events);
    }
}
