<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a deliberate is_demo flag for schools intended for public showcase.
 *
 * Data seeding was removed on 2026-09-29 (owner decision): fresh installs and
 * self-hosters must never receive demo schools from a migration. The single
 * canonical demo school is now seeded on purpose, only when a human runs it:
 *
 *     php artisan db:seed --class=DemoAcademySeeder
 *
 * Historic note: this migration used to seed "Lakeview Junior School" and
 * "Model Hill Secondary School"; those instances are retired via
 * demo:purge-schools (staging already, production after owner approval).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('schools', 'is_demo')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->boolean('is_demo')->default(false)->after('is_test');
            });
        }
    }

    public function down(): void
    {
        // Only the schema change is reversed. Demo rows are managed by
        // demo:purge-schools, never by a migration.
        if (Schema::hasColumn('schools', 'is_demo')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('is_demo');
            });
        }
    }
};
