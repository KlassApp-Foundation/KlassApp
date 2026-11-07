<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exam types become shared-or-owned: NULL is the shared catalog (EOT, BOT, MID)
 * that every school sees; a school's own rows carry its id. Existing rows stay
 * NULL, so no data migration and no change to exam_type_id references.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_types', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable()->after('id');
            $table->index(['school_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('exam_types', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'code']);
            $table->dropColumn('school_id');
        });
    }
};
