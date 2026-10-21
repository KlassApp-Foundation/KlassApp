<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The one-off teacher-subject import already ran in production.
 * Re-running up() must not print anything and must not insert rows.
 */
class ImportTeacherSubjectAssignmentsPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_teacher_subject_assignments_up_prints_nothing_and_inserts_nothing(): void
    {
        $before = DB::table('class_teacher_links')->count();

        $migration = require database_path(
            'migrations/2026_08_12_164737_import_teacher_subject_assignments.php'
        );

        ob_start();
        try {
            $migration->up();
        } finally {
            $output = ob_get_clean();
        }

        $this->assertSame('', $output);
        $this->assertSame($before, DB::table('class_teacher_links')->count());
    }
}
