<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DemoLinkedAtMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_linked_at_migration_can_run_twice_on_a_fresh_database(): void
    {
        $this->assertTrue(Schema::hasColumn('whatsapp_users', 'demo_linked_at'));

        $migration = require database_path(
            'migrations/2026_10_15_000001_add_demo_linked_at_to_whatsapp_users.php'
        );

        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('whatsapp_users', 'demo_linked_at'));

        $migration->down();
        $this->assertFalse(Schema::hasColumn('whatsapp_users', 'demo_linked_at'));

        $migration->down();

        $migration->up();
        $this->assertTrue(Schema::hasColumn('whatsapp_users', 'demo_linked_at'));
    }
}
