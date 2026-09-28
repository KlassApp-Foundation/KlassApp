<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ugandan admission form - basic field set (PR 5).
 * All new columns are nullable; no existing column is dropped or altered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            // Class / entry (step 1)
            $table->string('entry_term')->nullable();
            $table->string('entry_year')->nullable();
            $table->string('boarding_type')->nullable();

            // Student (step 2)
            $table->string('home_district')->nullable();
            $table->string('village_town')->nullable();
            $table->string('lin')->nullable();
            $table->string('birth_certificate')->nullable();

            // Academic (step 3)
            $table->string('last_class_completed')->nullable();
            $table->string('ple_index_number')->nullable();
            $table->string('ple_aggregate')->nullable();
            $table->string('uce_index_number')->nullable();
            $table->string('uce_results_summary')->nullable();

            // Parent or guardian (step 4)
            $table->string('father_relationship')->nullable();
            $table->string('father_alt_phone')->nullable();
            $table->string('father_district')->nullable();
            $table->boolean('father_on_whatsapp')->nullable();
            $table->string('mother_relationship')->nullable();
            $table->string('mother_alt_phone')->nullable();
            $table->string('mother_district')->nullable();
            $table->boolean('mother_on_whatsapp')->nullable();
            $table->string('emergency_contact_name_1')->nullable();

            // Health and support (step 5)
            $table->text('medical_conditions')->nullable();
            $table->text('special_needs')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn([
                'entry_term', 'entry_year', 'boarding_type',
                'home_district', 'village_town', 'lin', 'birth_certificate',
                'last_class_completed', 'ple_index_number', 'ple_aggregate',
                'uce_index_number', 'uce_results_summary',
                'father_relationship', 'father_alt_phone', 'father_district', 'father_on_whatsapp',
                'mother_relationship', 'mother_alt_phone', 'mother_district', 'mother_on_whatsapp',
                'emergency_contact_name_1',
                'medical_conditions', 'special_needs',
            ]);
        });
    }
};
