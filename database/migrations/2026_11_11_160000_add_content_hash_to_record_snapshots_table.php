<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A released snapshot stores the sha256 of its payload so a later reader can
 * see whether that payload still matches what was released.
 *
 * Safe to run twice. The table itself is not dropped here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('record_snapshots') || Schema::hasColumn('record_snapshots', 'content_hash')) {
            return;
        }

        Schema::table('record_snapshots', function (Blueprint $table): void {
            $table->string('content_hash', 64)->nullable()->after('payload');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('record_snapshots') || ! Schema::hasColumn('record_snapshots', 'content_hash')) {
            return;
        }

        Schema::table('record_snapshots', function (Blueprint $table): void {
            $table->dropColumn('content_hash');
        });
    }
};
