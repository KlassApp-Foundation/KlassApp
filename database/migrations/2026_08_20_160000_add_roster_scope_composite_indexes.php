<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // sections: RosterScopeService queries with school_id + status
        Schema::table('sections', function (Blueprint $table) {
            $table->index(['school_id', 'status'], 'sections_school_status_index');
        });

        // standards_link: school_id + academic_year_id + status (stream visibility)
        Schema::table('standards_link', function (Blueprint $table) {
            $table->index(
                ['school_id', 'academic_year_id', 'status'],
                'sl_school_ay_status_index',
            );
        });

        // marks: school_id + section_id + exam_id (archive/summary joins)
        Schema::table('marks', function (Blueprint $table) {
            $table->index(
                ['school_id', 'section_id', 'exam_id'],
                'marks_school_section_exam_index',
            );
        });

        // users: school_id + usergroup_id (teacher/student queries per school)
        Schema::table('users', function (Blueprint $table) {
            $table->index(
                ['school_id', 'usergroup_id'],
                'users_school_usergroup_index',
            );
        });
    }

    public function down(): void
    {
        // For each index, check if it exists in information_schema, then drop.
        $this->dropIndexIfExists('sections', 'sections_school_status_index');
        $this->dropIndexIfExists('standards_link', 'sl_school_ay_status_index');
        $this->dropMarksIndexWithFk();
        $this->dropIndexIfExists('users', 'users_school_usergroup_index');
    }

    /**
     * Drop an index if it exists by querying information_schema.
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $exists = DB::select(
            "SELECT 1 FROM information_schema.statistics
             WHERE table_schema = DATABASE()
             AND table_name = ?
             AND index_name = ?
             LIMIT 1",
            [$table, $indexName]
        );

        if (! empty($exists)) {
            DB::statement("ALTER TABLE `$table` DROP INDEX `$indexName`");
        }
    }

    /**
     * Drop the composite index on marks, handling the FK dependency on school_id.
     * The FK marks_school_id_foreign uses the composite index (school_id, section_id, exam_id)
     * because it's the only index starting with school_id. Must drop FK first.
     */
    private function dropMarksIndexWithFk(): void
    {
        $exists = DB::select(
            "SELECT 1 FROM information_schema.statistics
             WHERE table_schema = DATABASE()
             AND table_name = 'marks'
             AND index_name = 'marks_school_section_exam_index'
             LIMIT 1"
        );

        if (! empty($exists)) {
            // Drop the FK that depends on this composite index
            DB::statement('ALTER TABLE `marks` DROP FOREIGN KEY `marks_school_id_foreign`');
            // Drop index
            DB::statement('ALTER TABLE `marks` DROP INDEX `marks_school_section_exam_index`');
            // Re-add FK (MySQL will auto-create index on school_id)
            DB::statement(
                'ALTER TABLE `marks` ADD CONSTRAINT `marks_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE'
            );
        }
    }
};
