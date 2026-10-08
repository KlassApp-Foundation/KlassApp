<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\StudentAcademic;
use App\Models\User;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\DB;

/**
 * School fee position for the payments surfaces: collected this term,
 * outstanding, students in arrears, collection rate and the weekly sparkline.
 *
 * Shared by the admin payments kit and the bursar (accountant) payments page so
 * both report the same numbers from the same code.
 */
class FeePositionService
{
    /**
     * @return array{context: string, collected_label: string, collected_spark: array<int, float>, outstanding_label: string, arrears_label: string, rate_label: string, arrears_direction: ?string, arrears_delta_label: ?string, arrears_hint: ?string}
     */
    public function forSchool(int $schoolId): array
    {
        [$termLabel, $startsOn, $endsOn] = $this->currentTermWindow($schoolId);

        $collectedQuery = FeePayment::query()->where('school_id', $schoolId);
        if ($startsOn && $endsOn) {
            $collectedQuery->whereDate('paid_on', '>=', $startsOn)
                ->whereDate('paid_on', '<=', $endsOn);
        }
        $collected = (float) $collectedQuery->sum('amount');

        $categoriesByStandard = FeesCategories::query()
            ->where('school_id', $schoolId)
            ->get()
            ->groupBy('standard_id')
            ->map(fn ($rows) => (float) $rows->sum('amount'));

        $activeStudents = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->pluck('id');

        $latestAcademics = StudentAcademic::query()
            ->select('student_academics.user_id', 'standards_link.standard_id')
            ->join('standards_link', 'student_academics.standardLink_id', '=', 'standards_link.id')
            ->where('student_academics.school_id', $schoolId)
            ->whereNull('student_academics.deleted_at')
            ->whereIn('student_academics.user_id', $activeStudents)
            ->whereIn('student_academics.academic_year_id', function ($q) {
                $q->select('id')->from('academic_years')->where('status', 1);
            })
            ->orderByDesc('student_academics.id')
            ->get()
            ->unique('user_id');

        $paidByStudent = FeePayment::query()
            ->where('school_id', $schoolId)
            ->whereIn('user_id', $activeStudents)
            ->groupBy('user_id')
            ->select('user_id', DB::raw('SUM(amount) as total_paid'))
            ->pluck('total_paid', 'user_id');

        $expected = 0.0;
        $outstanding = 0.0;
        $arrears = 0;

        foreach ($latestAcademics as $row) {
            $due = (float) ($categoriesByStandard[$row->standard_id] ?? 0);
            if ($due <= 0) {
                continue;
            }
            $paid = (float) ($paidByStudent[$row->user_id] ?? 0);
            $balance = max(0, $due - $paid);
            $expected += $due;
            $outstanding += $balance;
            if ($balance > 0) {
                $arrears++;
            }
        }

        $rate = $expected > 0 ? (int) round(($expected - $outstanding) / $expected * 100) : 0;

        // Like-for-like trend for the arrears card: the SAME definition (standard
        // fee due vs payments received) evaluated BEFORE this term started, so the
        // card can show whether arrears are rising or falling. Null when there is
        // no term window to compare against — the card then renders no indicator.
        $arrearsDirection = null;
        $arrearsDeltaLabel = null;
        $arrearsHint = null;

        if ($startsOn) {
            $paidBeforeByStudent = FeePayment::query()
                ->where('school_id', $schoolId)
                ->whereIn('user_id', $activeStudents)
                ->whereDate('paid_on', '<', $startsOn)
                ->groupBy('user_id')
                ->select('user_id', DB::raw('SUM(amount) as total_paid'))
                ->pluck('total_paid', 'user_id');

            $arrearsBefore = 0;
            foreach ($latestAcademics as $row) {
                $due = (float) ($categoriesByStandard[$row->standard_id] ?? 0);
                if ($due <= 0) {
                    continue;
                }
                $paidBefore = (float) ($paidBeforeByStudent[$row->user_id] ?? 0);
                if (max(0, $due - $paidBefore) > 0) {
                    $arrearsBefore++;
                }
            }

            // Fewer students in arrears than at term start = 'down' = good
            // (the card passes invertDirection so the indicator reads positive).
            $arrearsDirection = $arrears <=> $arrearsBefore ? ($arrears > $arrearsBefore ? 'up' : 'down') : 'flat';
            $delta = $arrears - $arrearsBefore;
            $arrearsDeltaLabel = $delta === 0 ? null : (($delta > 0 ? '+' : '').$delta);
            $arrearsHint = 'vs term start';
        }

        // Real date-bucketed collections for the KPI sparkline (Laravel Trend,
        // flowframe/laravel-trend). Same window as "Collected this term"; falls
        // back to the last 8 weeks when there is no term window.
        $sparkStart = $startsOn ? \Illuminate\Support\Carbon::parse($startsOn) : now()->subWeeks(7)->startOfWeek();
        $sparkEnd = $endsOn ? \Illuminate\Support\Carbon::parse($endsOn) : now();

        $collectedSpark = Trend::query(FeePayment::query()->where('school_id', $schoolId))
            ->dateColumn('paid_on')   // bucket by when the money was actually paid
            ->between(start: $sparkStart, end: $sparkEnd)
            ->perWeek()
            ->sum('amount')
            ->map(fn (TrendValue $value) => (float) $value->aggregate)
            ->values()
            ->all();

        return [
            'context' => $termLabel ?: 'All classes',
            // Raw numbers for the dashboard first screen (PR1); the labels below
            // stay for the payments surfaces.
            'collected_raw' => $collected,
            'expected_raw' => $expected,
            'outstanding_raw' => $outstanding,
            'rate' => $rate,
            'collected_label' => 'UGX '.$this->compactMoney($collected),
            'collected_spark' => $collectedSpark,
            'outstanding_label' => 'UGX '.$this->compactMoney($outstanding),
            'arrears_label' => (string) $arrears,
            'rate_label' => $rate.'%',
            'arrears_direction' => $arrearsDirection,
            'arrears_delta_label' => $arrearsDeltaLabel,
            'arrears_hint' => $arrearsHint,
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function currentTermWindow(int $schoolId): array
    {
        $year = SiteHelper::getAcademicYear($schoolId);
        if (! $year) {
            return [null, null, null];
        }

        $term = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('status', 'current')
            ->orderByDesc('id')
            ->first();

        if (! $term) {
            $today = now()->toDateString();
            $term = AcademicTerm::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)
                ->whereDate('starts_on', '<=', $today)
                ->whereDate('ends_on', '>=', $today)
                ->orderByDesc('id')
                ->first();
        }

        if ($term) {
            $label = trim(($term->name ?: '').' '.($year->name ?: ''));

            return [
                $label !== '' ? $label.' · all classes' : ($year->name.' · all classes'),
                $term->starts_on?->toDateString(),
                $term->ends_on?->toDateString(),
            ];
        }

        return [$year->name ? $year->name.' · all classes' : null, null, null];
    }

    private function compactMoney(float $amount): string
    {
        if ($amount >= 1_000_000) {
            $m = $amount / 1_000_000;

            return rtrim(rtrim(number_format($m, 1, '.', ''), '0'), '.').'M';
        }
        if ($amount >= 1_000) {
            $k = $amount / 1_000;

            return rtrim(rtrim(number_format($k, 1, '.', ''), '0'), '.').'K';
        }

        return number_format($amount, 0);
    }
}
