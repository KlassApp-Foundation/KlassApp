<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Soft-launch backfill: schools with Toshi on drop to the safe default.
     * Raw DB update, row count logged, restoreable via down() from the
     * JSON capture written to storage/logs before the flip.
     *
     * toshi_enabled=1 rows are coerced back to the mode that implied them
     * ('assistant') first, so the single-match flip below can't strand a
     * non-assistant mode behind a stale enabled flag.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('schools', 'toshi_mode')) {
            return;
        }

        DB::table('schools')
            ->where('toshi_enabled', 1)
            ->whereNot('toshi_mode', 'assistant')
            ->update(['toshi_mode' => 'assistant']);

        $toFlip = DB::table('schools')->where('toshi_mode', 'assistant')->pluck('id');
        if ($toFlip->isEmpty()) {
            info('[toshi backfill] flipped 0 school(s): assistant=0, flag-only=0');

            return;
        }

        $logPath = storage_path('logs/toshi-backfill-restore-'.date('Ymd').'.json');
        file_put_contents($logPath, json_encode([
            'captured_at' => now()->toIso8601String(),
            'restore_ids' => $toFlip->all(),
        ], JSON_PRETTY_PRINT));

        $assistant = DB::table('schools')
            ->whereIn('id', $toFlip->all())
            ->update(['toshi_mode' => 'preview', 'toshi_enabled' => 0]);

        $staleFlag = DB::table('schools')
            ->where(fn ($q) => $q->where('toshi_enabled', 1)->orWhereNull('toshi_mode'))
            ->update(['toshi_enabled' => 0, 'toshi_mode' => 'preview']);

        $total = $assistant + $staleFlag;
        info("[toshi backfill] flipped {$total} school(s): assistant={$assistant}, flag-only={$staleFlag}");
    }

    public function down(): void
    {
        $logPath = storage_path('logs/toshi-backfill-restore-'.date('Ymd').'.json');
        $restored = is_file($logPath) ? (json_decode((string) file_get_contents($logPath), true)['restore_ids'] ?? []) : [];

        DB::table('schools')->whereIn('id', $restored)->update([
            'toshi_mode' => 'assistant',
            'toshi_enabled' => 1,
        ]);
    }
};
