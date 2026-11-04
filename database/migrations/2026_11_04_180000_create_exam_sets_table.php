<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A set is one "create this term's exams" action. Each new exam row records
 * which set created it. Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_sets')) {
            Schema::create('exam_sets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedInteger('academic_year_id');
                $table->unsignedBigInteger('academic_term_id');
                // users.id is an unsigned integer.
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
                $table->foreign('academic_term_id')->references('id')->on('academic_terms')->cascadeOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->index(['school_id', 'academic_term_id']);
            });
        }

        if (Schema::hasTable('exams') && ! Schema::hasColumn('exams', 'exam_set_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->unsignedBigInteger('exam_set_id')->nullable()->after('exam_type_id');
                $table->foreign('exam_set_id')->references('id')->on('exam_sets')->nullOnDelete();
                $table->index(['school_id', 'exam_set_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exams') && Schema::hasColumn('exams', 'exam_set_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropForeign(['exam_set_id']);
                $table->dropIndex(['school_id', 'exam_set_id']);
                $table->dropColumn('exam_set_id');
            });
        }

        Schema::dropIfExists('exam_sets');
    }
};
