<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE exam_marks_submissions MODIFY status ENUM('draft', 'submitted', 'locked', 'reopened', 'approved') NOT NULL DEFAULT 'draft'");
        }

        DB::table('exam_marks_submissions')
            ->where('approval_status', 'approved')
            ->whereIn('status', ['draft', 'submitted', 'locked', 'reopened'])
            ->update(['status' => 'approved']);
    }

    public function down(): void
    {
        DB::table('exam_marks_submissions')
            ->where('status', 'approved')
            ->update(['status' => 'submitted']);

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE exam_marks_submissions MODIFY status ENUM('draft', 'submitted', 'locked', 'reopened') NOT NULL DEFAULT 'draft'");
        }
    }
};
