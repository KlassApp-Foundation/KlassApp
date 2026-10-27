<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The legacy O-level scale belongs to the school, not to a national table.
 *
 * UNEB's pre-NLSC cut-offs were never published as one fixed table, so a school
 * that still reports on D1-F9 owns its own boundaries. This column stores that
 * school's table; when it is null the school sees the labelled example from
 * config/grading_uganda.php and can accept or edit it.
 *
 * Shape: a list of nine rows, best first:
 *   [{"grade":"D1","points":1,"min_score":75,"max_score":100}, ...]
 * Only the boundaries are the school's; the letters and points are fixed.
 *
 * Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schools') || Schema::hasColumn('schools', 'legacy_o_level_bands')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table): void {
            $table->json('legacy_o_level_bands')->nullable()->after('curriculum_profile');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('schools') || ! Schema::hasColumn('schools', 'legacy_o_level_bands')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn('legacy_o_level_bands');
        });
    }
};
