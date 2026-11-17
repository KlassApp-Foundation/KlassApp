<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * T-117: the wizard is the only setup surface (docs/onboarding/decisions-2026-11.md, D2).
 *
 * Any school still parked in the retired chat onboarding mode is moved to the
 * normal preview mode, with the legacy assistant flag off, so nothing can open a
 * scripted setup walkthrough. Assistant mode and preview mode are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('schools', 'toshi_mode')) {
            return;
        }

        DB::table('schools')
            ->where('toshi_mode', 'onboarding')
            ->update([
                'toshi_mode' => 'preview',
                'toshi_enabled' => 0,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Irreversible on purpose: the previous value was a setup path that no
        // longer exists, and restoring it would re-open a retired flow.
    }
};
