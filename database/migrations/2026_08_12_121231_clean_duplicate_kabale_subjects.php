<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $remap = [
            303 => 244, // Eng → ENGLISH
            309 => 245, // Mathematics → MTC
            304 => 249, // Lit I → LITERACY I
            305 => 250, // Lit Ii → LITERACY II
            306 => 301, // R R → Reading and Response
        ];

        foreach ($remap as $badId => $goodId) {
            if (!DB::table('subjects')->where('id', $badId)->exists()) continue;
            if (!DB::table('subjects')->where('id', $goodId)->exists()) continue;
            DB::table('marks')->where('subject_id', $badId)->update(['subject_id' => $goodId]);
        }

        DB::table('marks')->whereIn('subject_id', [302, 307, 308])->delete();
        DB::table('subjects')->whereIn('id', [302, 303, 304, 305, 306, 307, 308, 309])->delete();
    }

    public function down(): void
    {
        // Not reversible
    }
};
