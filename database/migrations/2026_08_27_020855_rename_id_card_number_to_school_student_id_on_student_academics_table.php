<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Rename student_academics.id_card_number → school_student_id.
 *
 * The old column name is misleading — it suggests a national ID card number,
 * but the WhatsApp lookup comment says "School's own student ID" and the only
 * code that ever wrote to it (AdmissionUser) was copying the platform's own
 * registration_number (KLS ID), which is already stored correctly in
 * klassapp_student_id. The column has zero non-null rows in production, so
 * this rename is safe with no data migration concerns.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add the new column
        Schema::table('student_academics', function (Blueprint $table) {
            $table->string('school_student_id')->nullable()->after('klassapp_student_id');
        });

        // Backfill: copy any existing data (none in production, but correct pattern)
        DB::statement(
            "UPDATE student_academics SET school_student_id = id_card_number WHERE id_card_number IS NOT NULL AND id_card_number != ''"
        );

        // Drop the old column
        Schema::table('student_academics', function (Blueprint $table) {
            $table->dropColumn('id_card_number');
        });
    }

    public function down(): void
    {
        Schema::table('student_academics', function (Blueprint $table) {
            $table->string('id_card_number')->nullable()->after('std_school_pay_number');
        });

        DB::statement(
            "UPDATE student_academics SET id_card_number = school_student_id WHERE school_student_id IS NOT NULL AND school_student_id != ''"
        );

        Schema::table('student_academics', function (Blueprint $table) {
            $table->dropColumn('school_student_id');
        });
    }
};
