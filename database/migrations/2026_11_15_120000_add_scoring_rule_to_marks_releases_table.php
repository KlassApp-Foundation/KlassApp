<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The scoring rule in force when a release ran. Null means a release from
 * before this column: blanks stay out of the aggregate, and the row is not
 * rewritten. A new release stores missing_as_zero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marks_releases', function (Blueprint $table): void {
            if (! Schema::hasColumn('marks_releases', 'scoring_rule')) {
                $table->string('scoring_rule', 32)->nullable()->after('report_kind');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marks_releases', function (Blueprint $table): void {
            if (Schema::hasColumn('marks_releases', 'scoring_rule')) {
                $table->dropColumn('scoring_rule');
            }
        });
    }
};
