<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A brand-new database must be able to use CACHE_STORE=database.
 *
 * The repo shipped without Laravel's cache migration, so the first data
 * migration that called Cache::forget() aborted on an empty schema with
 * "Table ...cache doesn't exist" during deploy commands.
 */
class CacheStoreSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_cache_tables_exist_after_migrations(): void
    {
        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasTable('cache_locks'));

        foreach (['key', 'value', 'expiration'] as $column) {
            $this->assertTrue(Schema::hasColumn('cache', $column), "cache.{$column} is missing");
        }

        foreach (['key', 'owner', 'expiration'] as $column) {
            $this->assertTrue(Schema::hasColumn('cache_locks', $column), "cache_locks.{$column} is missing");
        }
    }

    public function test_the_database_cache_store_round_trips(): void
    {
        config(['cache.default' => 'database']);

        Cache::put('cache-schema-probe', 'ok', 60);

        $this->assertSame('ok', Cache::get('cache-schema-probe'));

        $lock = Cache::lock('cache-schema-lock', 5);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    /**
     * Migrations run in filename order. On an empty database the cache tables
     * must exist before the first migration that calls Cache::forget(), or the
     * deploy fails exactly as it did on the fresh Cloud environment.
     */
    public function test_the_cache_migration_runs_before_migrations_that_use_the_cache(): void
    {
        $files = glob(database_path('migrations/*.php'));
        $this->assertIsArray($files);

        $cacheMigrations = array_values(array_filter(
            $files,
            fn (string $path): bool => str_contains(basename($path), 'create_cache_table'),
        ));
        $this->assertCount(1, $cacheMigrations, 'Expected exactly one create_cache_table migration.');

        $cacheMigration = basename($cacheMigrations[0]);

        $cacheUsers = [];
        foreach ($files as $path) {
            $name = basename($path);
            if ($name === $cacheMigration) {
                continue;
            }
            if (preg_match('/Cache::(forget|put|get|remember)\(/', (string) file_get_contents($path)) === 1) {
                $cacheUsers[] = $name;
            }
        }

        $this->assertNotEmpty($cacheUsers, 'Expected at least one migration that touches the cache.');

        foreach ($cacheUsers as $user) {
            $this->assertLessThan(
                $user,
                $cacheMigration,
                "{$cacheMigration} must sort before {$user} so the cache tables exist first."
            );
        }
    }
}
