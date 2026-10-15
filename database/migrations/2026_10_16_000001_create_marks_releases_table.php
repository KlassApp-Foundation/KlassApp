<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 9 Tier 2: a release of one exam's marks, for one class or the whole
 * school, now or at a scheduled time. Times are stored in UTC.
 * Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marks_releases')) {
            return;
        }

        Schema::create('marks_releases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('exam_id');
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
            // "school" (every class with this exam) or "class" (one class and stream).
            $table->string('scope', 16);
            $table->unsignedInteger('standard_link_id')->nullable();
            $table->foreign('standard_link_id')->references('id')->on('standards_link')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->dateTime('released_at')->nullable();
            // scheduled → sending → released, or cancelled.
            $table->string('status', 16)->default('scheduled');
            $table->unsignedInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->string('timezone', 64)->nullable();
            $table->json('result')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['school_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marks_releases');
    }
};
