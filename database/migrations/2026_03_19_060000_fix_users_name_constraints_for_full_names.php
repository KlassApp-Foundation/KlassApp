<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'name')) {
            // Ensure no null values remain before enforcing NOT NULL.
            DB::statement("UPDATE users SET name = CONCAT('user-', id) WHERE name IS NULL OR TRIM(name) = ''");
            try {
                DB::statement('ALTER TABLE users MODIFY name VARCHAR(100) NOT NULL');
            } catch (\Exception $e) {
                // SQLite does not support MODIFY — Laravel's Schema::change() would also fail here.
                // The column is already NOT NULL in the base migration, so this is only needed for MySQL.
            }
        }

        if (Schema::hasTable('users') && $this->hasIndex('users', 'users_name_unique')) {
            Schema::table('users', function ($table) {
                $table->dropUnique('users_name_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'name')) {
            DB::statement('ALTER TABLE users MODIFY name VARCHAR(255) NULL');
        }

        if (Schema::hasTable('users') && ! $this->hasIndex('users', 'users_name_unique')) {
            Schema::table('users', function ($table) {
                $table->unique('name', 'users_name_unique');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        // SQLite doesn't have information_schema — use PRAGMA instead
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(1) as aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return isset($result->aggregate) && (int) $result->aggregate > 0;
    }
};
