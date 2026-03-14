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
            $table->string("name");
            $table->string("term");
             // match existing table types
            $table->unsignedInteger("standard_id"); 
            $table->unsignedBigInteger('school_id'); 
            $table->unsignedInteger('academic_year_id'); 
            $table->unsignedInteger('subject_id'); 
            $table->unsignedInteger('teacher_id'); 
            // foreign keys
            // foreign keys
            $table->foreign('standard_id')->references('id')->on('standards')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
            // hddvncdvcbnc
            $table->timestamps();
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
