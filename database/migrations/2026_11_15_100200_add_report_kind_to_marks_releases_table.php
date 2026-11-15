<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The report type in force when a release happens. Null on rows released
 * before this column existed: those stay the progressive card and are not
 * rewritten when the school later picks a different type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marks_releases', function (Blueprint $table): void {
            if (! Schema::hasColumn('marks_releases', 'report_kind')) {
                $table->string('report_kind', 32)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marks_releases', function (Blueprint $table): void {
            if (Schema::hasColumn('marks_releases', 'report_kind')) {
                $table->dropColumn('report_kind');
            }
        });
    }
};
