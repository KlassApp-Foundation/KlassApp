<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schools get their own motto column.
 *
 * The motto lived only as a `school_details` meta row (`moto`), which is what the
 * report-card identity reads. That makes it the odd one out among school identity
 * fields, which are columns, and it cannot be queried or constrained. This adds the
 * column and backfills it, treating the `-` sentinel as "not set".
 *
 * Deliberately non-destructive: the legacy meta rows are left untouched, reads fall
 * back to them during the transition, and writes keep both in step. Rolling this back
 * is therefore safe, because nothing depended on the column before it existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('schools', 'motto')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->string('motto', 100)->nullable()->after('name');
            });
        }

        // Backfill from the legacy meta, ignoring the '-' sentinel and empty values.
        DB::table('school_details')
            ->where('meta_key', 'moto')
            ->whereNotNull('meta_value')
            ->whereNotIn('meta_value', ['-', ''])
            ->orderBy('id')
            ->pluck('meta_value', 'school_id')
            ->each(function ($value, $schoolId) {
                DB::table('schools')
                    ->where('id', $schoolId)
                    ->whereNull('motto')
                    ->update(['motto' => $value]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('schools', 'motto')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('motto');
            });
        }
    }
};
