<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An absent student is stored as absent=1 with a null mark and grade AB.
 * The mark column has to be nullable for that. Reversible: absent rows are
 * given 0 before the column is made required again, then absent is dropped.
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

        DB::table('marks')->where('absent', true)->whereNull('marks')->update(['marks' => 0]);

        Schema::table('marks', function (Blueprint $table) {
            $table->decimal('marks', 5, 2)->nullable(false)->change();
            $table->dropColumn('absent');
        });
    }
};
