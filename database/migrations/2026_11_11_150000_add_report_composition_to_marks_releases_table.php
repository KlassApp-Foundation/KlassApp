<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The end-of-term mix in force when a release happened. Later edits of
 * schools.report_composition do not rewrite this copy.
 *
 * Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marks_releases') || Schema::hasColumn('marks_releases', 'report_composition')) {
            return;
        }

        Schema::table('marks_releases', function (Blueprint $table): void {
            $table->json('report_composition')->nullable()->after('release_reason');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('marks_releases') || ! Schema::hasColumn('marks_releases', 'report_composition')) {
            return;
        }

        Schema::table('marks_releases', function (Blueprint $table): void {
            $table->dropColumn('report_composition');
        });
    }
};
