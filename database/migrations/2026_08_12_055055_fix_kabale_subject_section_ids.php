<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Kabale P.2: reassign marks from wrong-section subjects to correct ones.
     *
     * The import created duplicate subjects because firstOrCreate matched on
     * (school_id + name + standard_id) but the unique constraint is
     * (school_id + section_id + name). Marks point to old subjects (234/236/
     * 241/242) with wrong section_ids; correct duplicates (244/245/249/250)
     * already exist at section_id=46 with no marks. This migration reassigns
     * marks and cleans up orphaned subjects.
     */
    public function up(): void
    {
        $remap = [
            234 => 245, // Mathematics → MTC
            236 => 244, // ENGLISH → ENGLISH
            241 => 249, // LITERACY I → LITERACY I
            242 => 250, // LITERACY II → LITERACY II
        ];

        foreach ($remap as $oldId => $newId) {
            if (!DB::table('subjects')->where('id', $oldId)->exists()) continue;
            if (!DB::table('subjects')->where('id', $newId)->exists()) continue;

            DB::table('marks')->where('subject_id', $oldId)->update(['subject_id' => $newId]);

            if (!DB::table('marks')->where('subject_id', $oldId)->exists()) {
                DB::table('subjects')->where('id', $oldId)->delete();
            }
        }
    }

    public function down(): void
    {
        // Not reversible
    }
};
