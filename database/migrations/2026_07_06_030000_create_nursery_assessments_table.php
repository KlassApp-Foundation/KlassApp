<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursery_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('student_id');
            $table->unsignedBigInteger('academic_term_id');
            $table->unsignedBigInteger('exam_id')->nullable();
            $table->string('domain', 50);               // Literacy, Numeracy, Motor Skills, Social/Emotional
            $table->string('rating', 30);                // Excellent, Good, Satisfactory, Needs Improvement
            $table->text('remarks')->nullable();

            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('academic_term_id')->references('id')->on('academic_terms')->onDelete('cascade');
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('set null');

            $table->unique(['student_id', 'academic_term_id', 'domain'], 'nursery_assessment_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursery_assessments');
    }
};
