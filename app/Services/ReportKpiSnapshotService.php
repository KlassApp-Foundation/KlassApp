<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Materialized EOT KPI snapshots for the admin dashboard and the report-cards page.
 *
 * Pattern reference: Academico's `cached_reports` + `academico:build-report`
 * (truncate + rebuild, config anchored) — re-implemented on KlassApp's own data
 * model. Before this, `ReportCardsController::computeEotKpis()` ran three raw-SQL
 * aggregates on EVERY dashboard load and every report-cards load.
 *
 * The contract:
 *   - `computeLive()` — the original aggregate, unchanged, still the single source
 *     of truth (the rebuild calls it, so what is stored is exactly what KlassApp
 *     always computed).
 *   - `rebuild()`     — truncate + rebuild into `report_kpi_snapshots`.
 *   - `kpis()`        — the read path used by dashboards/report cards: the snapshot
 *     when present and fresh, otherwise live compute. A missing, never-built or
 *     stale snapshot can therefore never show wrong numbers; it only costs the old
 *     query time.
 */

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportKpiSnapshotService
{
    public const METRIC_CLASS = 'class';

    public const METRIC_SUBJECT = 'subject';

    public const METRIC_GENDER = 'gender';

    /** metric column => key in the returned array */
    private const METRIC_KEYS = [
        self::METRIC_CLASS => 'perClass',
        self::METRIC_SUBJECT => 'perSubject',
        self::METRIC_GENDER => 'perGender',
    ];

    /**
     * Read path for the dashboard + report cards. Same shape as the old live
     * aggregate: ['perClass' => [['label' => .., 'value' => ..]], 'perSubject' => [...], 'perGender' => [...]].
     */
    public function kpis(int $schoolId, ?int $academicTermId = null): array
    {
        if (! config('report_kpis.enabled', true)) {
            return $this->computeLive($schoolId, $academicTermId);
        }

        return $this->read($schoolId, $academicTermId)
            ?? $this->computeLive($schoolId, $academicTermId);
    }

    /**
     * Snapshot read. Returns null when there is nothing usable (absent, or older
     * than config('report_kpis.stale_after_minutes')) so the caller can fall back.
     */
    public function read(int $schoolId, ?int $academicTermId = null): ?array
    {
        $rows = DB::table('report_kpi_snapshots')
            ->where('school_id', $schoolId)
            ->where('academic_term_id', $academicTermId)
            ->orderBy('metric')
            ->orderBy('sort_order')
            ->get(['metric', 'label', 'value', 'computed_at']);

        if ($rows->isEmpty()) {
            return null;
        }

        $staleAfter = (int) config('report_kpis.stale_after_minutes', 90);
        if ($staleAfter > 0) {
            $newest = $rows->max('computed_at');
            if ($newest === null || Carbon::parse($newest)->lt(now()->subMinutes($staleAfter))) {
                return null;
            }
        }

        $out = ['perClass' => [], 'perSubject' => [], 'perGender' => []];

        foreach ($rows as $row) {
            $key = self::METRIC_KEYS[$row->metric] ?? null;
            if ($key === null) {
                continue;
            }

            $out[$key][] = [
                'label' => $row->label,
                'value' => $row->value === null ? null : (float) $row->value,
            ];
        }

        return $out;
    }

    /**
     * Truncate + rebuild. Returns the number of snapshot rows written.
     *
     * @param  int|null  $schoolId  limit to one school (null = every school that has marks)
     * @param  int|null  $academicTermId  when given, rebuild ONLY that term scope
     */
    public function rebuild(?int $schoolId = null, ?int $academicTermId = null): int
    {
        $delete = DB::table('report_kpi_snapshots');
        if ($schoolId !== null) {
            $delete->where('school_id', $schoolId);
        }
        if ($academicTermId !== null) {
            $delete->where('academic_term_id', $academicTermId);
        }
        $delete->delete();

        $schoolIds = $schoolId !== null ? [$schoolId] : $this->schoolIdsWithMarks();

        $now = now();
        $rows = [];

        foreach ($schoolIds as $id) {
            foreach ($this->scopesToSnapshot((int) $id, $academicTermId) as $termId) {
                $kpis = $this->computeLive((int) $id, $termId);

                foreach (self::METRIC_KEYS as $metric => $key) {
                    $order = 0;
                    foreach ($kpis[$key] as $item) {
                        $item = (array) $item;
                        $rows[] = [
                            'school_id' => (int) $id,
                            'academic_term_id' => $termId,
                            'metric' => $metric,
                            'label' => (string) ($item['label'] ?? ''),
                            'value' => ($item['value'] ?? null) === null ? null : (float) $item['value'],
                            'sort_order' => $order++,
                            'computed_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
        }

        $chunk = max(1, (int) config('report_kpis.insert_chunk', 500));
        foreach (array_chunk($rows, $chunk) as $batch) {
            DB::table('report_kpi_snapshots')->insert($batch);
        }

        return count($rows);
    }

    /**
     * The scopes to snapshot for one school: the all-terms scope (null, used by the
     * dashboard) plus one scope per academic term (used by the report-cards page).
     *
     * @return list<int|null>
     */
    public function scopesToSnapshot(int $schoolId, ?int $onlyTermId = null): array
    {
        if ($onlyTermId !== null) {
            return [$onlyTermId];
        }

        $scopes = [];

        if (config('report_kpis.snapshot_all_terms', true)) {
            $scopes[] = null;
        }

        if (config('report_kpis.snapshot_per_term', true)) {
            $termIds = DB::table('marks as m')
                ->join('exams as e', 'm.exam_id', '=', 'e.id')
                ->join('exam_types as et', 'e.exam_type_id', '=', 'et.id')
                ->where('m.school_id', $schoolId)
                ->where('et.contributes_to_report_total', 1)
                ->whereNotNull('e.academic_term_id')
                ->distinct()
                ->pluck('e.academic_term_id')
                ->all();

            foreach ($termIds as $termId) {
                $scopes[] = (int) $termId;
            }
        }

        return $scopes;
    }

    /** @return list<int> */
    public function schoolIdsWithMarks(): array
    {
        return DB::table('marks as m')
            ->join('exams as e', 'm.exam_id', '=', 'e.id')
            ->join('exam_types as et', 'e.exam_type_id', '=', 'et.id')
            ->where('et.contributes_to_report_total', 1)
            ->distinct()
            ->pluck('m.school_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * The original live aggregate, unchanged (moved here verbatim so the snapshot
     * and the live fallback can never drift from KlassApp's established numbers).
     */
    public function computeLive(int $schoolId, ?int $academicTermId = null): array
    {
        // --- Per class: average of per-student EOT totals, grouped by section ---
        $perClass = DB::table('marks as m')
            ->join('exams as e', 'm.exam_id', '=', 'e.id')
            ->join('exam_types as et', 'e.exam_type_id', '=', 'et.id')
            ->where('m.school_id', $schoolId)
            ->where('et.contributes_to_report_total', 1)
            ->when($academicTermId, fn ($q) => $q->where('e.academic_term_id', $academicTermId))
            ->select('m.section_id', DB::raw('SUM(m.marks) as total'))
            ->groupBy('m.student_id', 'm.section_id');

        $perClass = DB::table(DB::raw("({$perClass->toSql()}) as student_totals"))
            ->mergeBindings($perClass)
            ->join('sections as s', 'student_totals.section_id', '=', 's.id')
            ->select('s.name as label', DB::raw('ROUND(AVG(student_totals.total), 1) as value'))
            ->groupBy('s.name', 'student_totals.section_id')
            ->orderBy('s.name')
            ->get()
            ->toArray();

        // --- Per subject: average mark per subject ---
        $perSubject = DB::table('marks as m')
            ->join('exams as e', 'm.exam_id', '=', 'e.id')
            ->join('exam_types as et', 'e.exam_type_id', '=', 'et.id')
            ->join('subjects as sub', 'm.subject_id', '=', 'sub.id')
            ->where('m.school_id', $schoolId)
            ->where('et.contributes_to_report_total', 1)
            ->when($academicTermId, fn ($q) => $q->where('e.academic_term_id', $academicTermId))
            ->select('sub.name as label', DB::raw('ROUND(AVG(m.marks), 1) as value'))
            ->groupBy('sub.name', 'm.subject_id')
            ->orderBy('sub.name')
            ->get()
            ->toArray();

        // --- Per gender: average of per-student EOT totals, split by gender ---
        $studentTotalsForGender = DB::table('marks as m')
            ->join('exams as e', 'm.exam_id', '=', 'e.id')
            ->join('exam_types as et', 'e.exam_type_id', '=', 'et.id')
            ->where('m.school_id', $schoolId)
            ->where('et.contributes_to_report_total', 1)
            ->when($academicTermId, fn ($q) => $q->where('e.academic_term_id', $academicTermId))
            ->select('m.student_id', DB::raw('SUM(m.marks) as total'))
            ->groupBy('m.student_id');

        $perGender = DB::table(DB::raw("({$studentTotalsForGender->toSql()}) as student_totals"))
            ->mergeBindings($studentTotalsForGender)
            ->join('users as u', 'student_totals.student_id', '=', 'u.id')
            ->join('userprofiles as up', 'u.id', '=', 'up.user_id')
            ->whereIn('up.gender', ['male', 'female'])
            ->select(DB::raw("CASE WHEN up.gender = 'male' THEN 'Male' ELSE 'Female' END as label"), DB::raw('ROUND(AVG(student_totals.total), 1) as value'))
            ->groupBy('up.gender')
            ->orderBy('up.gender')
            ->get()
            ->toArray();

        return [
            'perClass' => $perClass,
            'perSubject' => $perSubject,
            'perGender' => $perGender,
        ];
    }
}
