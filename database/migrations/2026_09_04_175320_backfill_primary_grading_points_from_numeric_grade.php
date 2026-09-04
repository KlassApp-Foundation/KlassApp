<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Primary 1–9 bands were seeded with points=null while grade held "1"…"9".
 * Report TOTAL AGG only summed `points`, so cards showed subject AGGs that
 * looked graded (or "D") while TOTAL AGG stayed 0. Backfill points from the
 * numeric grade label where points is still null.
 */
return new class extends Migration
{
    public function up(): void
    {
        for ($points = 1; $points <= 9; $points++) {
            DB::table('school_grading_systems')
                ->whereNull('points')
                ->where('grade', (string) $points)
                ->update(['points' => $points]);
        }
    }

    public function down(): void
    {
        // Irreversible data repair — do not null out points that may have been
        // set intentionally after this migration.
    }
};
