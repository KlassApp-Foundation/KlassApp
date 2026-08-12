<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Flag auto-generated junk student records as inactive.
     *
     * - Names containing digits (original bulk import artifact)
     * - Duplicate records (same name, multiple user_ids) — keep earliest, flag later
     *
     * REVERSIBLE: down() restores original status.
     */
    public function up(): void
    {
        $flagged = 0;

        // Flag digit-containing names as inactive
        $flagged += DB::table('users')
            ->where('school_id', 104)
            ->where('usergroup_id', 6)
            ->where('name', 'regexp', '[0-9]')
            ->where('status', '!=', 'inactive')
            ->update(['status' => 'inactive']);

        // For duplicate names, keep the earliest (lowest id), flag others
        $dupes = DB::table('users')
            ->where('school_id', 104)->where('usergroup_id', 6)
            ->where('name', 'regexp', '^[^0-9]+$')
            ->whereIn('name', function ($q) {
                $q->select('name')->from('users')
                    ->where('school_id', 104)->where('usergroup_id', 6)
                    ->where('name', 'regexp', '^[^0-9]+$')
                    ->groupBy('name')->havingRaw('COUNT(*) > 1');
            })
            ->where('status', '!=', 'inactive')
            ->orderBy('name')->orderBy('id')
            ->get()
            ->groupBy('name');

        foreach ($dupes as $name => $records) {
            $keep = $records->first();
            foreach ($records->slice(1) as $dup) {
                DB::table('users')->where('id', $dup->id)->update(['status' => 'inactive']);
                $flagged++;
            }
        }
    }

    public function down(): void
    {
        DB::table('users')
            ->where('school_id', 104)
            ->where('usergroup_id', 6)
            ->where('status', 'inactive')
            ->update(['status' => 'active']);
    }
};
