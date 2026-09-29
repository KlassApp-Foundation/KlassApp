<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The public admission form no longer collects height, weight, or the second
 * emergency contact (removed with the Ugandan field set), but those legacy
 * columns were NOT NULL - the form could not save at all. Make them nullable;
 * no data is changed or removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            // The form has collected a surname since the restyle but the table
            // never had the column; add it (nullable, no data change).
            $table->string('lastname')->nullable();

            $table->longText('reason_for_leaving')->nullable()->change();
            foreach ([
                'height',
                'weight',
                'emergency_contact_1',
                'relation_with_student_1',
                'emergency_contact_2',
                'relation_with_student_2',
                'father_occupation',
                'mother_name',
            ] as $column) {
                $table->string($column)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn('lastname');

            $table->longText('reason_for_leaving')->nullable(false)->change();
            foreach ([
                'height', 'weight',
                'emergency_contact_1', 'relation_with_student_1',
                'emergency_contact_2', 'relation_with_student_2',
                'father_occupation', 'mother_name',
            ] as $column) {
                $table->string($column)->nullable(false)->change();
            }
        });
    }
};
