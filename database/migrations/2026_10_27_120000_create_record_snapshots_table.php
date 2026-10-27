<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verifiable records, step 1: one immutable snapshot per learner per term.
 *
 * A release writes these rows; nothing ever updates or deletes one. A
 * correction inserts a new version and marks the old row superseded. Hashing,
 * signing and QR codes are later steps and are not in this table.
 *
 * Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('record_snapshots')) {
            return;
        }

        Schema::create('record_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->unsignedInteger('student_id');
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedInteger('academic_year_id');
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->unsignedBigInteger('academic_term_id');
            $table->foreign('academic_term_id')->references('id')->on('academic_terms')->cascadeOnDelete();

            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('supersedes_id')->nullable();
            $table->string('reason', 500)->nullable();
            $table->json('payload');
            $table->timestamp('released_at')->nullable();
            $table->unsignedInteger('released_by')->nullable();
            $table->unsignedBigInteger('release_id')->nullable();
            // current | superseded | revoked
            $table->string('state', 16)->default('current');
            $table->timestamps();

            $table->unique(
                ['school_id', 'student_id', 'academic_year_id', 'academic_term_id', 'version'],
                'record_snapshot_version_unique',
            );
            $table->index(['school_id', 'academic_term_id', 'state']);
            $table->index(['student_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_snapshots');
    }
};
