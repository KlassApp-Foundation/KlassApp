<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Demo schools are not customers, so they must not be counted as platform activity.
 *
 * `is_demo` marks a school for public showcase; `is_test` keeps a tenant out of the
 * Superadmin platform metrics and the "recently joined" feed. The demo seed set only the
 * former, so the two demo schools and their synthetic users were being counted as real
 * school and user growth. This backfills the second flag and applies to any future demo
 * school too, since it is keyed on is_demo rather than on specific ids.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('schools')
            ->where('is_demo', true)
            ->where('is_test', false)
            ->update(['is_test' => true]);
    }

    public function down(): void
    {
        // Reverting would put demo schools back into platform reporting. That was the bug,
        // so the down path intentionally leaves the flags alone.
    }
};
