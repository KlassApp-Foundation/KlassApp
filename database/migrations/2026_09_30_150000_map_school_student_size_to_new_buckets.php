<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Legacy approximate-size buckets (pre-2026-09-30 wording) mapped to the
     * final three options. Old buckets collapse into the nearest new one.
     */
    public const MAP = [
        'Under 100 students' => 'Up to 500',
        '100-300 students' => 'Up to 500',
        '300-500 students' => 'Up to 500',
        '500+ students' => 'Up to 1,000',
    ];

    public function up(): void
    {
        foreach (self::MAP as $legacy => $current) {
            DB::table('schools')
                ->where('student_size', $legacy)
                ->update(['student_size' => $current]);
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: the legacy wording is retired and the
        // new buckets are a strictly coarser partition of the old ones.
    }
};
