<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inclusive aggregate ranges for a division label.
 * standard_id null is the school's default. A class level overrides it.
 * No rows means the built-in ranges (12 or less is 1, then 2, 3, 4, and U).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('report_division_ranges')) {
            return;
        }

        Schema::create('report_division_ranges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->unsignedInteger('standard_id')->nullable();
            $table->foreign('standard_id')->references('id')->on('standards')->cascadeOnDelete();
            $table->string('label', 8);
            $table->integer('min_aggregate')->nullable();
            $table->integer('max_aggregate')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['school_id', 'standard_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_division_ranges');
    }
};
