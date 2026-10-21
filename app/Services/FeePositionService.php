<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
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

    /**
     * Active students in this school whose fee category is not fully paid.
     *
     * Same rows the payments dashboard lists. Optional filters narrow the
     * students (class stream) or, when a term has its own categories, the
     * categories and the payments recorded against them.
     *
     * @param  array{standard_link_ids?: list<int>|null, academic_term_id?: int|null}  $filters
     * @return list<array{id: int, name: string, lookup_name: string, balance: float, parents: int, payment_id: ?int, standard_link_id: int, class_label: string}>
     */
    public function studentsWithBalance(int $schoolId, array $filters = []): array
    {
        $termId = isset($filters['academic_term_id']) ? (int) $filters['academic_term_id'] : null;
        if ($termId !== null && $termId <= 0) {
            $termId = null;
        }
        if ($termId !== null) {
            $termBelongs = AcademicTerm::query()
                ->where('school_id', $schoolId)
                ->whereKey($termId)
                ->exists();
            if (! $termBelongs) {
                return [];
            }
        }

        $categoryModels = FeesCategories::query()
            ->where('school_id', $schoolId)
            ->get();
        $termSpecific = false;
        if ($termId !== null) {
            $matched = $categoryModels->filter(
                fn ($row) => $row->academic_term_id !== null && (int) $row->academic_term_id === $termId
            )->values();
            if ($matched->isNotEmpty()) {
                $categoryModels = $matched;
                $termSpecific = true;
            }
        }

        $categoriesByStandard = $categoryModels
            ->groupBy('standard_id')
            ->map(fn ($rows) => (float) $rows->sum('amount'));
        $categoryIds = $categoryModels->pluck('id')->map(fn ($id) => (int) $id)->all();

        $activeStudents = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('id');

        $academics = StudentAcademic::query()
            ->select(
                'student_academics.id',
                'student_academics.user_id',
                'standards_link.standard_id',
                'standards_link.id as standard_link_id',
                'standards_link.stream as stream_name',
                'sections.name as section_name',
                'standards.name as standard_name',
            )
            ->join('standards_link', 'student_academics.standardLink_id', '=', 'standards_link.id')
            ->leftJoin('sections', 'standards_link.section_id', '=', 'sections.id')
            ->leftJoin('standards', 'standards_link.standard_id', '=', 'standards.id')
            ->where('student_academics.school_id', $schoolId)
            ->where('standards_link.school_id', $schoolId)
            ->whereNull('student_academics.deleted_at')
            ->whereIn('student_academics.user_id', $activeStudents->keys())
            ->whereIn('student_academics.academic_year_id', function ($q) use ($schoolId) {
                $q->select('id')->from('academic_years')
                    ->where('school_id', $schoolId)
                    ->where('status', 1);
            });

        if (array_key_exists('standard_link_ids', $filters) && is_array($filters['standard_link_ids'])) {
            $linkIds = array_values(array_unique(array_map('intval', $filters['standard_link_ids'])));
            if ($linkIds === []) {
                return [];
            }
            $academics->whereIn('standards_link.id', $linkIds);
        }

        $latestAcademics = $academics
            ->orderByDesc('student_academics.id')
            ->get()
            ->unique('user_id');

        $owing = [];
        $labels = [];
        foreach ($latestAcademics as $row) {
            $due = (float) ($categoriesByStandard[$row->standard_id] ?? 0);
            if ($due <= 0) {
                continue;
            }
            $userId = (int) $row->user_id;
            $owing[$userId] = $due;
            $labels[$userId] = [
                'standard_link_id' => (int) $row->standard_link_id,
                'class_label' => $this->classLabel($row->standard_name, $row->section_name, $row->stream_name),
            ];
        }
        if ($owing === []) {
            return [];
        }

        $paidQuery = FeePayment::query()
            ->where('school_id', $schoolId)
            ->whereIn('user_id', array_keys($owing));
        if ($termSpecific && $categoryIds !== []) {
            $paidQuery->whereIn('fee_category_id', $categoryIds);
        }
        $paidByStudent = $paidQuery
            ->groupBy('user_id')
            ->select('user_id', DB::raw('SUM(amount) as total_paid'))
            ->pluck('total_paid', 'user_id');

        $latestPaymentQuery = FeePayment::query()
            ->where('school_id', $schoolId)
            ->whereIn('user_id', array_keys($owing));
        if ($termSpecific && $categoryIds !== []) {
            $latestPaymentQuery->whereIn('fee_category_id', $categoryIds);
        }
        $latestPayment = $latestPaymentQuery
            ->orderByDesc('id')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $parentCounts = StudentParentLink::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->whereIn('student_id', array_keys($owing))
            ->select('student_id', DB::raw('COUNT(*) as parents'))
            ->groupBy('student_id')
            ->pluck('parents', 'student_id');

        $rows = [];
        foreach ($owing as $userId => $due) {
            $balance = max(0, $due - (float) ($paidByStudent[$userId] ?? 0));
            if ($balance <= 0) {
                continue;
            }
            $user = $activeStudents->get($userId);
            if (! $user) {
                continue;
            }
            $rows[] = [
                'id' => $userId,
                'name' => $user->displayName ?: $user->name,
                'lookup_name' => (string) $user->name,
                'balance' => $balance,
                'parents' => (int) ($parentCounts[$userId] ?? 0),
                'payment_id' => $latestPayment->get($userId)?->id,
                'standard_link_id' => $labels[$userId]['standard_link_id'],
                'class_label' => $labels[$userId]['class_label'],
            ];
        }

        return $rows;
    }

    private function classLabel(mixed $standard, mixed $section, mixed $stream): string
    {
        $standardName = trim((string) $standard);
        $sectionName = trim((string) $section);
        $streamName = trim((string) $stream);
        $label = trim($standardName.' '.$sectionName);
        if ($streamName !== '' && strcasecmp($streamName, $sectionName) !== 0) {
            $label = trim($label.' · '.$streamName);
        }

        return $label;
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
