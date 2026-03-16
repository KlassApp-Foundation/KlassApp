<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

    if(!Schema::hasTable("marks")){

   
        Schema::create('marks', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('teacher_id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('subject_id');
            $table->unsignedBigInteger('exam_id');

            // Other columns
            $table->decimal('marks', 5, 2); // e.g., 100.00 max
            $table->string('comment')->nullable();

            // Define foreign key constraints
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();

            $table->timestamps();
        });
         }
    }

    public function down(): void
    {
        Schema::dropIfExists('marks');
    }
};