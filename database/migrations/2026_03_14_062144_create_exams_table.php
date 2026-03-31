<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if(!Schema::hasTable("exams")){

        
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->dateTime("scheduled_at")->nullable();
            $table->enum("status", ["done", "postponed", "undone"])->default("undone");
             // match existing table types
            // $table->unsignedInteger("exam_type_id"); 
            $table->unsignedInteger("standard_id"); 
            $table->unsignedBigInteger('school_id'); 
            $table->unsignedInteger('academic_year_id'); 
            $table->unsignedInteger('subject_id'); 
            $table->unsignedInteger('teacher_id');
            $table->unsignedInteger('section_id'); 

            // foreign keys
            // foreign keys
            $table->foreign('section_id')->references('id')->on('sections')->cascadeOnDelete();
             $table->foreign('standard_id')->references('id')->on('standards')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
            // hddvncdvcbnc
            $table->timestamps();
            $table->softDeletes();
        });
    }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
