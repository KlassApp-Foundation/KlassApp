<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('exams')
            ->where('school_id', 104)
            ->where('section_id', 51)
            ->where('standard_id', 43)
            ->where('academic_term_id', 89)
            ->delete();
    }

    public function down(): void
    {
        // Not reversible — stale exams deleted deliberately
    }
};
