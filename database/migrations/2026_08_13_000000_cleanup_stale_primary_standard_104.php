<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // School 104: PR #259 split the stale "primary" standard (id 43) into
        // "primary_lower" (54) and "primary_upper" (55), but the split was left
        // incomplete:
        //   - P.1/P.2/P.3/P.7 got new standards_link rows (78/79/80/81) but the
        //     old rows (52/53/54/58) were never removed, so the Class filter on
        //     /admin/students listed them twice. The stale rows sort first and
        //     match zero students, so selecting one looked like a broken filter.
        //   - P.4/P.5/P.6 were never moved to primary_upper; ~530 students plus
        //     their subject/exams still sat under the stale "primary" standard.
        //
        // This mirrors the P.7 stale-exam cleanup (2026_08_12_182607_...).

        $schoolId = 104;
        $staleStandardId = 43; // "primary" (pre-restructure)
        $primaryUpperId = 55;  // "primary_upper"

        // 1. Re-point the real P.4/P.5/P.6 links to primary_upper. Students keep
        //    the same standards_link ids, so no student re-linking is required.
        DB::table('standards_link')
            ->where('school_id', $schoolId)
            ->where('standard_id', $staleStandardId)
            ->whereIn('id', [55, 56, 57])
            ->update(['standard_id' => $primaryUpperId]);

        // 2. Soft-delete the duplicate P.1/P.2/P.3/P.7 links (zero students).
        DB::table('standards_link')
            ->where('school_id', $schoolId)
            ->where('standard_id', $staleStandardId)
            ->whereIn('id', [52, 53, 54, 58])
            ->update(['deleted_at' => now()]);

        // 3. Re-point the stray subject (SCI) to primary_upper.
        DB::table('subjects')
            ->where('school_id', $schoolId)
            ->where('standard_id', $staleStandardId)
            ->update(['standard_id' => $primaryUpperId]);

        // 4. Re-point exams under the stale standard to primary_upper.
        DB::table('exams')
            ->where('school_id', $schoolId)
            ->where('standard_id', $staleStandardId)
            ->update(['standard_id' => $primaryUpperId]);

        // 5. Delete the stale grading scale (primary_upper already has its own).
        DB::table('school_grading_systems')
            ->where('school_id', $schoolId)
            ->where('standard_id', $staleStandardId)
            ->delete();

        // 6. Soft-delete the stale "primary" standard itself.
        DB::table('standards')
            ->where('id', $staleStandardId)
            ->where('school_id', $schoolId)
            ->update(['deleted_at' => now()]);
    }

    public function down(): void
    {
        // Not reversible — stale pre-restructure data cleaned up deliberately.
    }
};
