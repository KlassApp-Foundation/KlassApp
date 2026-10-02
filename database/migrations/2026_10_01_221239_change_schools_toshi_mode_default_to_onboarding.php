<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New inserts that omit toshi_mode land on onboarding (setup guide on).
     * Existing rows are left unchanged — preview remains a per-school fallback.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('schools', 'toshi_mode')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE schools MODIFY toshi_mode VARCHAR(32) NOT NULL DEFAULT 'onboarding'");

            return;
        }

        Schema::table('schools', function (Blueprint $table) {
            $table->string('toshi_mode', 32)->default('onboarding')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('schools', 'toshi_mode')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE schools MODIFY toshi_mode VARCHAR(32) NOT NULL DEFAULT 'preview'");

            return;
        }

        Schema::table('schools', function (Blueprint $table) {
            $table->string('toshi_mode', 32)->default('preview')->change();
        });
    }
};
