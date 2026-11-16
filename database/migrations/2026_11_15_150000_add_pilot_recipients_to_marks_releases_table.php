<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who a release may message when the school chooses a pilot list.
 * Null is the whole class, which is the release that already existed.
 * An empty list messages nobody: it must not fall back to the class.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marks_releases', function (Blueprint $table): void {
            if (! Schema::hasColumn('marks_releases', 'pilot_recipients')) {
                $table->json('pilot_recipients')->nullable()->after('release_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marks_releases', function (Blueprint $table): void {
            if (Schema::hasColumn('marks_releases', 'pilot_recipients')) {
                $table->dropColumn('pilot_recipients');
            }
        });
    }
};
