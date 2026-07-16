<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'default_deadline')) {
                $table->timestamp('default_deadline')->nullable()->after('scheduled_at');
            }
        });

        Schema::create('exam_marks_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->unsignedInteger('class_id');
            $table->unsignedInteger('subject_id');
            $table->unsignedInteger('teacher_id');

            $table->enum('status', ['draft', 'submitted', 'locked', 'reopened'])->default('draft');
            $table->timestamp('deadline')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->unsignedInteger('reopened_by')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'class_id', 'subject_id'], 'exam_marks_submissions_unique');

            $table->foreign('class_id')->references('id')->on('sections')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reopened_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_marks_submissions');

        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'default_deadline')) {
                $table->dropColumn('default_deadline');
            }
        });
    }
};
