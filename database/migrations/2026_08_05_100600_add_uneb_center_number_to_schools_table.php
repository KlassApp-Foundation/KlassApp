<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NEW nullable column for optional UNEB examination centre numbers.
 * No existing schools/school_details column covered this — reported explicitly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('schools', 'uneb_center_number')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->string('uneb_center_number', 50)->nullable()->after('ministry_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('schools', 'uneb_center_number')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('uneb_center_number');
            });
        }
    }
};
