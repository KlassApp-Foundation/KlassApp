<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Rebuild the materialized EOT KPI snapshots (truncate + rebuild, the Academico
 * `academico:build-report` shape). Scheduled from App\Console\Kernel; safe to run
 * on demand from the CLI.
 */

namespace App\Console\Commands;

use App\Services\ReportKpiSnapshotService;
use Illuminate\Console\Command;

class RebuildReportKpis extends Command
{
    protected $signature = 'report-kpis:rebuild
                            {--school= : Rebuild only this school id}
                            {--term= : Rebuild only this academic term id}';

    protected $description = 'Truncate + rebuild the EOT KPI snapshots the dashboard and report cards read';

    public function handle(ReportKpiSnapshotService $snapshots): int
    {
        if (! config('report_kpis.enabled', true)) {
            $this->warn('report_kpis.enabled is false — nothing to rebuild (readers compute live).');

            return self::SUCCESS;
        }

        $schoolId = $this->option('school') !== null && $this->option('school') !== ''
            ? (int) $this->option('school')
            : null;
        $termId = $this->option('term') !== null && $this->option('term') !== ''
            ? (int) $this->option('term')
            : null;

        $started = microtime(true);
        $rows = $snapshots->rebuild($schoolId, $termId);
        $ms = (int) round((microtime(true) - $started) * 1000);

        $this->info(sprintf(
            'report-kpis: rebuilt %d snapshot row(s) in %d ms%s%s.',
            $rows,
            $ms,
            $schoolId !== null ? " (school {$schoolId})" : '',
            $termId !== null ? " (term {$termId})" : ''
        ));

        return self::SUCCESS;
    }
}
