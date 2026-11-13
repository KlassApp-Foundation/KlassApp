<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One combined report per term is written today. The name column is what a
 * later year-end report will use: another row, same school and term, different
 * name, with its own component rows. exam_types.short_name is the phone tab
 * label a school can set. Empty means the first word of the set name.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exam_types') && ! Schema::hasColumn('exam_types', 'short_name')) {
            Schema::table('exam_types', function (Blueprint $table) {
                $table->string('short_name', 24)->nullable()->after('code');
            });
        }

        if (! Schema::hasTable('combined_reports')) {
            Schema::create('combined_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('academic_term_id')->nullable()->index();
                $table->string('name');
                $table->timestamps();
                $table->unique(['school_id', 'academic_term_id', 'name'], 'combined_reports_school_term_name');
            });
        }

        if (! Schema::hasTable('combined_report_components')) {
            Schema::create('combined_report_components', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('combined_report_id')->index();
                $table->unsignedBigInteger('exam_type_id')->index();
                $table->string('mode', 16);
                $table->decimal('weight', 6, 2)->nullable();
                $table->timestamps();
                $table->unique(['combined_report_id', 'exam_type_id'], 'combined_report_components_report_type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('combined_report_components');
        Schema::dropIfExists('combined_reports');
        if (Schema::hasTable('exam_types') && Schema::hasColumn('exam_types', 'short_name')) {
            Schema::table('exam_types', function (Blueprint $table) {
                $table->dropColumn('short_name');
            });
        }
    }
};
