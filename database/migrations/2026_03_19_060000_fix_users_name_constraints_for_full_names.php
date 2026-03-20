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
            DB::statement('ALTER TABLE users MODIFY name VARCHAR(100) NOT NULL');
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
        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT COUNT(1) as aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return isset($result->aggregate) && (int) $result->aggregate > 0;
    }
};
