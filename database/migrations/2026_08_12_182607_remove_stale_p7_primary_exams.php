<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('exams')
            ->where('school_id', 104)
            ->where('section_id', DB::table('sections')->where('school_id', 104)->where('name', 'P.7')->value('id'))
            ->where('standard_id', DB::table('standards')->where('name', 'primary')->value('id'))
            ->where('academic_term_id', 89)
            ->delete();
    }

    public function down(): void
    {
        // Not reversible — stale exams deleted deliberately
    }
};
