<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The grade bands, division ranges and ranking method in force when a
 * release ran. Null means a release from before this column. That row is
 * not rewritten: a reprint keeps the default division table and the
 * automatic ranking, and it still reads the school's current grade bands
 * because no copy was stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marks_releases', function (Blueprint $table): void {
            if (! Schema::hasColumn('marks_releases', 'progressive_rules')) {
                $table->json('progressive_rules')->nullable()->after('scoring_rule');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marks_releases', function (Blueprint $table): void {
            if (Schema::hasColumn('marks_releases', 'progressive_rules')) {
                $table->dropColumn('progressive_rules');
            }
        });
    }
};
