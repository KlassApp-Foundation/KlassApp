<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $scale = [
            95 => 1, 85 => 2, 75 => 3, 65 => 4,
            60 => 5, 50 => 6, 45 => 7, 40 => 8,  0 => 9,
        ];

        foreach ($scale as $minScore => $points) {
            DB::table('school_grading_systems')
                ->where('school_id', 104)
                ->whereIn('standard_id', [44, 54])
                ->where('min_score', $minScore)
                ->whereNull('points')
                ->update(['points' => $points]);
        }
    }

    public function down(): void
    {
        DB::table('school_grading_systems')
            ->where('school_id', 104)
            ->whereIn('standard_id', [44, 54])
            ->update(['points' => null]);
    }
};
