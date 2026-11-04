<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive fee-structure columns.
 *
 * Existing rows are left in place. A whole-school fee saved before this
 * migration is still one row per grading tier. The fee list groups those
 * copies when they share a name, amount, type, duration and term and together
 * cover every tier. Nothing here deletes or merges those rows.
 *
 * standard_id becomes nullable so a new whole-school fee can be stored as one
 * row that is not tied to a single tier. Rows that already have a standard_id
 * are unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('fees_categories', 'duration')) {
            Schema::table('fees_categories', function (Blueprint $table) {
                $table->string('duration', 20)->default('termly');
            });
        }

        if (! Schema::hasColumn('fees_categories', 'applies_school_wide')) {
            Schema::table('fees_categories', function (Blueprint $table) {
                $table->boolean('applies_school_wide')->default(false);
            });
        }

        if (! Schema::hasTable('fee_category_sections')) {
            Schema::create('fee_category_sections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fee_category_id');
                $table->unsignedInteger('section_id');
                $table->timestamps();

                $table->unique(['fee_category_id', 'section_id'], 'fee_category_sections_fee_section_unique');
                $table->foreign('fee_category_id')
                    ->references('id')->on('fees_categories')
                    ->cascadeOnDelete();
                $table->foreign('section_id')
                    ->references('id')->on('sections')
                    ->cascadeOnDelete();
            });
        }

        $this->makeStandardIdNullable();
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_category_sections');

        if (Schema::hasColumn('fees_categories', 'applies_school_wide')) {
            Schema::table('fees_categories', function (Blueprint $table) {
                $table->dropColumn('applies_school_wide');
            });
        }

        if (Schema::hasColumn('fees_categories', 'duration')) {
            Schema::table('fees_categories', function (Blueprint $table) {
                $table->dropColumn('duration');
            });
        }
    }

    private function makeStandardIdNullable(): void
    {
        $column = collect(Schema::getColumns('fees_categories'))->firstWhere('name', 'standard_id');
        if ($column === null || ($column['nullable'] ?? false)) {
            return;
        }

        Schema::table('fees_categories', function (Blueprint $table) {
            $table->dropForeign(['standard_id']);
        });

        Schema::table('fees_categories', function (Blueprint $table) {
            $table->unsignedInteger('standard_id')->nullable()->change();
        });

        Schema::table('fees_categories', function (Blueprint $table) {
            $table->foreign('standard_id')
                ->references('id')->on('standards')
                ->cascadeOnDelete();
        });
    }
};
