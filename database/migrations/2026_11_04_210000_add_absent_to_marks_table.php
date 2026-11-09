<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An absent student is stored as absent=1 with a null mark and grade AB.
 * The mark column has to be nullable for that.
 *
 * down() refuses while any absent row exists. Turning AB into 0 would
 * change results. Clear those rows first, then roll back.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marks')) {
            return;
        }

        if (! Schema::hasColumn('marks', 'absent')) {
            Schema::table('marks', function (Blueprint $table) {
                $table->boolean('absent')->default(false);
            });
        }

        Schema::table('marks', function (Blueprint $table) {
            $table->decimal('marks', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('marks') || ! Schema::hasColumn('marks', 'absent')) {
            return;
        }

        if (DB::table('marks')->where('absent', true)->exists()) {
            throw new \RuntimeException(
                'Cannot roll back absent marks while marks.absent = true rows exist. Remove or resolve those absences first. Rolling back must not turn AB into 0.'
            );
        }

        Schema::table('marks', function (Blueprint $table) {
            $table->decimal('marks', 5, 2)->nullable(false)->change();
            $table->dropColumn('absent');
        });
    }
};
