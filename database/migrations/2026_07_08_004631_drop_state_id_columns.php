<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $isSqlite = $driver === 'sqlite';

        Schema::table('schools', function (Blueprint $table) use ($isSqlite) {
            if (!$isSqlite) {
                $table->dropColumn('state_id');
            }
        });

        Schema::table('cities', function (Blueprint $table) use ($isSqlite) {
            if (!$isSqlite) {
                $table->dropColumn('state_id');
            }
        });

        Schema::table('userprofiles', function (Blueprint $table) use ($isSqlite) {
            if (!$isSqlite) {
                $table->dropColumn('state_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->integer('state_id')->unsigned()->nullable();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->integer('state_id')->unsigned()->nullable();
        });

        Schema::table('userprofiles', function (Blueprint $table) {
            $table->integer('state_id')->unsigned()->nullable();
        });
    }
};
