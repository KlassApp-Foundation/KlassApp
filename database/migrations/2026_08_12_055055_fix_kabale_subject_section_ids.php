<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix Kabale P.2 subjects (school 104) that were imported with wrong section_id.
     *
     * The import script's firstOrCreate did not update section_id on pre-existing
     * subjects, so Mathematics (234), ENGLISH (236), LITERACY I (241), and
     * LITERACY II (242) retained their original section_id values (43/45) instead
     * of P.2's section (46).
     *
     * This migration is idempotent — safe to run on environments without Kabale data.
     */
    public function up(): void
    {
        $p2section = DB::table('standards_link')
            ->join('sections', 'standards_link.section_id', '=', 'sections.id')
            ->where('standards_link.school_id', 104)
            ->where('standards_link.standard_id', 54)
            ->where('sections.name', 'P.2')
            ->value('standards_link.section_id');

        if (!$p2section) {
            return;
        }

        DB::table('subjects')
            ->where('school_id', 104)
            ->whereIn('id', [234, 236, 241, 242])
            ->update(['section_id' => $p2section]);
    }

    public function down(): void
    {
        // No reverse — original section_ids are not uniquely recoverable
    }
};
