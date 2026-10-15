<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarksReleasesMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const COLUMNS = ['school_id', 'exam_id', 'scope', 'standard_link_id', 'scheduled_at', 'released_at', 'status', 'created_by', 'timezone', 'result'];

    public function test_task9_marks_releases_migration_can_run_twice(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');
        $this->assertTrue(Schema::hasColumns('marks_releases', self::COLUMNS));

        $migration = require database_path('migrations/2026_10_16_000001_create_marks_releases_table.php');

        $migration->up();
        $migration->up();
        $this->assertTrue(Schema::hasColumns('marks_releases', self::COLUMNS));

        $migration->down();
        $this->assertFalse(Schema::hasTable('marks_releases'));
        $migration->down();

        $migration->up();
        $this->assertTrue(Schema::hasColumns('marks_releases', self::COLUMNS));
    }
}
