<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DemoStudentIdMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_task9_demo_student_id_migration_can_run_twice_on_a_fresh_database(): void
    {
        $this->assertTrue(Schema::hasColumn('whatsapp_users', 'demo_student_id'));

        $migration = require database_path('migrations/2026_10_15_000002_add_demo_student_id_to_whatsapp_users.php');

        $migration->up();
        $migration->up();
        $this->assertTrue(Schema::hasColumn('whatsapp_users', 'demo_student_id'));

        $migration->down();
        $this->assertFalse(Schema::hasColumn('whatsapp_users', 'demo_student_id'));
        $migration->down();

        $migration->up();
        $this->assertTrue(Schema::hasColumn('whatsapp_users', 'demo_student_id'));
    }
}
