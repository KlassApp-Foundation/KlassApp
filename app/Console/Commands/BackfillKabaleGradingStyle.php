<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\Standard;
use App\Models\StandardLink;
use Illuminate\Console\Command;

class BackfillKabaleGradingStyle extends Command
{
    protected $signature = 'kabale:backfill-grading-style
        {--dry-run : Report counts without writing}
        {--school= : School ID (defaults to 104 for Kabale Junior)}';

    protected $description = 'Backfill grading_style on standards and sub_group on standards_link for Kabale Junior School';

    /**
     * Grading-style mapping: which standard names get which grading_style.
     * nursery + primary_lower → total_marks (lower-level, report shows total marks)
     * primary + primary_upper → aggregate (upper-level, report shows aggregate/division)
     * Everything else → aggregate (safe default for O-Level / A-Level).
     */
    private const GRADING_STYLE_MAP = [
        'nursery'        => 'total_marks',
        'primary_lower'  => 'total_marks',
        'primary'        => 'aggregate',
        'primary_upper'  => 'aggregate',
        'o-level'         => 'aggregate',
        'a-level'         => 'aggregate',
    ];

    /**
     * Sub-group mapping: which standard names belong to which sub-group.
     * primary_lower → 'lower' (P.1–P.3)
     * primary_upper → 'upper' (P.4–P.7)
     * Nursery, O-Level, A-Level → no sub_group (NULL).
     */
    private const SUB_GROUP_MAP = [
        'primary_lower' => 'lower',
        'primary_upper' => 'upper',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $schoolId = (int) ($this->option('school') ?? 104);

        $school = School::find($schoolId);
        if (! $school) {
            $this->error("School ID {$schoolId} not found.");
            return self::FAILURE;
        }

        $this->info("School: {$school->name} (ID {$schoolId})");
        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be written.');
        }

        // ── Backfill grading_style on standards ──────────────────────────

        $standards = Standard::where('school_id', $schoolId)->get();
        $gradedCount = 0;
        $skippedCount = 0;

        foreach ($standards as $standard) {
            $mapped = self::GRADING_STYLE_MAP[$standard->name] ?? null;

            if ($mapped === null) {
                $this->line("  Standard '{$standard->name}' — no grading_style mapping, skipping.");
                $skippedCount++;
                continue;
            }

            if ($standard->grading_style !== null && $standard->grading_style === $mapped) {
                $this->line("  Standard '{$standard->name}' — already '{$mapped}', skipping.");
                $skippedCount++;
                continue;
            }

            $previous = $standard->grading_style ?? 'NULL';
            $this->line("  Standard '{$standard->name}' — grading_style: {$previous} → {$mapped}");

            if (! $dryRun) {
                $standard->grading_style = $mapped;
                $standard->save();
            }
            $gradedCount++;
        }

        $this->info("Standards: {$gradedCount} updated, {$skippedCount} skipped.");

        // ── Backfill sub_group on standards_link ─────────────────────────

        $links = StandardLink::where('school_id', $schoolId)->get();
        $subGroupedCount = 0;
        $linkSkippedCount = 0;

        foreach ($links as $link) {
            $standardName = $link->standard?->name;
            $mapped = $standardName ? (self::SUB_GROUP_MAP[$standardName] ?? null) : null;

            if ($mapped === null) {
                $this->line("  Link ID {$link->id} (standard: {$standardName}) — no sub_group mapping, skipping.");
                $linkSkippedCount++;
                continue;
            }

            if ($link->sub_group !== null && $link->sub_group === $mapped) {
                $this->line("  Link ID {$link->id} (standard: {$standardName}) — already '{$mapped}', skipping.");
                $linkSkippedCount++;
                continue;
            }

            $previous = $link->sub_group ?? 'NULL';
            $this->line("  Link ID {$link->id} (standard: {$standardName}) — sub_group: {$previous} → {$mapped}");

            if (! $dryRun) {
                $link->sub_group = $mapped;
                $link->save();
            }
            $subGroupedCount++;
        }

        $this->info("StandardLinks: {$subGroupedCount} updated, {$linkSkippedCount} skipped.");
        $this->info('Done.');

        return self::SUCCESS;
    }
}
