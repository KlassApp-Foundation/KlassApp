<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * EOT KPI snapshots — materialized report/dashboard aggregates.
 *
 * The numbers are computed by `php artisan report-kpis:rebuild` (truncate +
 * rebuild, like Academico's `academico:build-report`) into `report_kpi_snapshots`.
 * Dashboards and report cards read the snapshot instead of running the raw-SQL
 * aggregate on every request.
 */
return [
    // Master switch. When false, readers compute live (pre-snapshot behaviour).
    'enabled' => env('REPORT_KPIS_SNAPSHOT_ENABLED', true),

    // A snapshot older than this is ignored and the caller recomputes live, so a
    // stale snapshot can never display wrong numbers — it only costs the old
    // query time. 0 disables the staleness check (snapshot always trusted).
    'stale_after_minutes' => (int) env('REPORT_KPIS_STALE_AFTER_MINUTES', 90),

    // How often the scheduler rebuilds: every_fifteen_minutes | every_thirty_minutes
    // | hourly | daily.
    'rebuild_frequency' => env('REPORT_KPIS_REBUILD_FREQUENCY', 'hourly'),

    // Time used when rebuild_frequency = daily (site timezone).
    'daily_at' => env('REPORT_KPIS_DAILY_AT', '01:30'),

    // Snapshot the all-terms aggregate (used by the admin dashboard).
    'snapshot_all_terms' => env('REPORT_KPIS_SNAPSHOT_ALL_TERMS', true),

    // Also snapshot one scope per academic term (used by the report-cards page).
    'snapshot_per_term' => env('REPORT_KPIS_SNAPSHOT_PER_TERM', true),

    // Rows per insert batch while rebuilding.
    'insert_chunk' => (int) env('REPORT_KPIS_INSERT_CHUNK', 500),
];
