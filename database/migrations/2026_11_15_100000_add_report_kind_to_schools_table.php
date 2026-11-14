<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each school chooses one report type. Existing schools stay on the
 * progressive card, which is the card they already print.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (! Schema::hasColumn('schools', 'report_kind')) {
                $table->string('report_kind', 32)->default('progressive')->after('report_template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (Schema::hasColumn('schools', 'report_kind')) {
                $table->dropColumn('report_kind');
            }
        });
    }
};
