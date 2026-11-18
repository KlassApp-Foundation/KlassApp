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
 *   - `computeLive()` — each stored mark is rounded once, then the average of those
 *     whole numbers is rounded once. The rebuild stores that result.
 *   - `rebuild()`     — truncate + rebuild into `report_kpi_snapshots`.
 *   - `kpis()`        — the read path used by dashboards/report cards: the snapshot
 *     when present and fresh, otherwise live compute. A missing, never-built or
 *     stale snapshot can therefore never show wrong numbers; it only costs the old
 *     query time.
 */

namespace App\Services;

use App\Services\Results\ResultsPolicy;
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
     * Live aggregate. Each stored mark is rounded once. A class or gender figure
     * is the average of those per-learner totals, rounded once. A subject figure
     * is the average of the rounded marks, rounded once.
     *
     * @return array{perClass: list<object>, perSubject: list<object>, perGender: list<object>}
     */
    public function computeLive(int $schoolId, ?int $academicTermId = null): array
    {
        $rows = DB::table('marks as m')
            ->join('exams as e', 'm.exam_id', '=', 'e.id')
            ->join('exam_types as et', 'e.exam_type_id', '=', 'et.id')
            ->join('subjects as sub', 'm.subject_id', '=', 'sub.id')
            ->leftJoin('sections as s', 'm.section_id', '=', 's.id')
            ->leftJoin('userprofiles as up', 'm.student_id', '=', 'up.user_id')
            ->where('m.school_id', $schoolId)
            ->where('et.contributes_to_report_total', 1)
            ->whereNotNull('m.marks')
            ->when($academicTermId, fn ($q) => $q->where('e.academic_term_id', $academicTermId))
            ->orderBy('m.id')
            ->get([
                'm.id',
                'm.student_id',
                'm.section_id',
                'm.subject_id',
                'm.marks',
                's.name as section_name',
                'sub.name as subject_name',
                'up.gender',
            ])
            ->unique('id');

        $bySubject = [];
        $byStudentSection = [];
        $byStudent = [];
        foreach ($rows as $row) {
            $whole = ResultsPolicy::roundTo((float) $row->marks);
            $subjectId = (int) $row->subject_id;
            $bySubject[$subjectId]['label'] = (string) $row->subject_name;
            $bySubject[$subjectId]['marks'][] = $whole;

            if ($row->section_name !== null && $row->section_id !== null) {
                $sectionKey = ((int) $row->student_id).'|'.((int) $row->section_id);
                $byStudentSection[$sectionKey]['section_id'] = (int) $row->section_id;
                $byStudentSection[$sectionKey]['label'] = (string) $row->section_name;
                $byStudentSection[$sectionKey]['total'] = ($byStudentSection[$sectionKey]['total'] ?? 0.0) + $whole;
            }

            if (in_array($row->gender, ['male', 'female'], true)) {
                $studentId = (int) $row->student_id;
                $byStudent[$studentId]['gender'] = (string) $row->gender;
                $byStudent[$studentId]['total'] = ($byStudent[$studentId]['total'] ?? 0.0) + $whole;
            }
        }

        $perSubject = [];
        foreach ($bySubject as $bucket) {
            $perSubject[] = (object) [
                'label' => $bucket['label'],
                'value' => ResultsPolicy::roundTo(array_sum($bucket['marks']) / count($bucket['marks'])),
            ];
        }
        usort($perSubject, fn (object $a, object $b) => strcmp($a->label, $b->label));

        $bySection = [];
        foreach ($byStudentSection as $bucket) {
            $sectionId = $bucket['section_id'];
            $bySection[$sectionId]['label'] = $bucket['label'];
            $bySection[$sectionId]['totals'][] = $bucket['total'];
        }
        $perClass = [];
        foreach ($bySection as $bucket) {
            $perClass[] = (object) [
                'label' => $bucket['label'],
                'value' => ResultsPolicy::roundTo(array_sum($bucket['totals']) / count($bucket['totals'])),
            ];
        }
        usort($perClass, fn (object $a, object $b) => strcmp($a->label, $b->label));

        $byGender = ['female' => [], 'male' => []];
        foreach ($byStudent as $bucket) {
            $byGender[$bucket['gender']][] = $bucket['total'];
        }
        $perGender = [];
        foreach (['female' => 'Female', 'male' => 'Male'] as $gender => $label) {
            if ($byGender[$gender] === []) {
                continue;
            }
            $perGender[] = (object) [
                'label' => $label,
                'value' => ResultsPolicy::roundTo(array_sum($byGender[$gender]) / count($byGender[$gender])),
            ];
        }

        return [
            'perClass' => $perClass,
            'perSubject' => $perSubject,
            'perGender' => $perGender,
        ];
    }
}
