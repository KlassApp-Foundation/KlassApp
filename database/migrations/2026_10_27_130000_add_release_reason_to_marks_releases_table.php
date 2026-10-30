<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A release that releases a sitting already released must say why. The reason
 * is stored on the release and copied onto the new snapshot version; there is
 * no default.
 *
 * Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marks_releases') || Schema::hasColumn('marks_releases', 'release_reason')) {
            return;
        }

        Schema::table('marks_releases', function (Blueprint $table): void {
            $table->string('release_reason', 500)->nullable()->after('result');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('marks_releases') || ! Schema::hasColumn('marks_releases', 'release_reason')) {
            return;
        }

        Schema::table('marks_releases', function (Blueprint $table): void {
            $table->dropColumn('release_reason');
        });
    }
};
