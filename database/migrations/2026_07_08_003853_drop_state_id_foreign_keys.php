<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $isSqlite = $driver === 'sqlite';

        Schema::table('schools', function (Blueprint $table) use ($isSqlite) {
            if (!$isSqlite) {
                $table->dropForeign(['state_id']);
            }
        });

        Schema::table('cities', function (Blueprint $table) use ($isSqlite) {
            if (!$isSqlite) {
                $table->dropForeign(['state_id']);
            }
        });

        if (!$isSqlite) {
            DB::statement('ALTER TABLE cities MODIFY state_id INT UNSIGNED NULL');
        }

        Schema::table('userprofiles', function (Blueprint $table) use ($isSqlite) {
            if (!$isSqlite) {
                $table->dropForeign(['state_id']);
            }
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $isSqlite = $driver === 'sqlite';

        if ($isSqlite) {
            return;
        }

        Schema::table('schools', function (Blueprint $table) {
            $table->foreign('state_id')->references('id')->on('states');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->foreign('state_id')->references('id')->on('states');
        });

        Schema::table('userprofiles', function (Blueprint $table) {
            $table->foreign('state_id')->references('id')->on('states');
        });
    }
};
