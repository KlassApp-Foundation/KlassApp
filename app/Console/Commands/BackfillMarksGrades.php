<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillMarksGrades extends Command
{
    protected $signature = 'kabale:backfill-grades
                            {--school=104 : School ID to backfill}
                            {--dry-run : Show what would be updated without writing}';

    protected $description = 'Backfill marks.grade for existing marks using the grading system score ranges';

    public function handle(): int
    {
        $schoolId = (int) $this->option('school');
        $dryRun = $this->option('dry-run');

        $grades = DB::table('school_grading_systems')
            ->where('school_id', $schoolId)
            ->get();

        if ($grades->isEmpty()) {
            $this->warn("No grading system records found for school {$schoolId}.");
            return 0;
        }

        $this->info("School {$schoolId}: " . $grades->count() . " grading bands across " . $grades->pluck('standard_id')->unique()->count() . " standards.");

        $totalUpdated = 0;
        foreach ($grades as $g) {
            $query = DB::table('marks')
                ->join('exams', 'marks.exam_id', '=', 'exams.id')
                ->where('marks.school_id', $schoolId)
                ->where('exams.standard_id', $g->standard_id)
                ->whereNull('marks.grade')
                ->where('marks.marks', '>=', $g->min_score)
                ->where('marks.marks', '<=', $g->max_score);

            $count = $query->count();
            if ($count > 0) {
                $this->line("  {$g->grade} (standard {$g->standard_id}, {$g->min_score}-{$g->max_score}): {$count} rows");
                if (!$dryRun) {
                    $query->update(['marks.grade' => $g->grade]);
                }
                $totalUpdated += $count;
            }
        }

        if ($dryRun) {
            $this->info("\nDry run — {$totalUpdated} rows would be updated.");
        } else {
            $this->info("\nBackfilled {$totalUpdated} marks.grade rows.");
        }

        return 0;
    }
}
