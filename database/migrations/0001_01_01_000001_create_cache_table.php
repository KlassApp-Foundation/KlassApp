<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The database cache store's two tables.
 *
 * Laravel's skeleton ships this migration; this repo never had it, so a fresh
 * environment with CACHE_STORE=database had nowhere to write. A data migration
 * calls Cache::forget() (2026_08_13_030000_backfill_school104_streams:689) and
 * aborted with "Table ...cache doesn't exist" during deploy commands, before
 * migrate could finish.
 *
 * The 0001_01_01 timestamp is deliberate: migrations run in filename order, so
 * on a brand-new database this runs before every data migration that touches the
 * cache. On an existing database it is simply the next pending migration and
 * adds the tables there.
 *
 * hasTable() keeps it safe to run twice (standing rule 35) and safe on an
 * environment where someone created the tables by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};
