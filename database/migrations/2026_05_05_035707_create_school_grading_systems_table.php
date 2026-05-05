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
        Schema::create('school_grading_systems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("school_id");
            $table->unsignedInteger("standard_id"); 

            $table->integer("rank");
            $table->string('grade'); // A, B, C...
            $table->integer('min_score'); // 80
            $table->integer('max_score'); // 100
            $table->string('remark')->nullable(); // Excellent, Pass, etc.

            $table->foreign("school_id")->references("id")->on("schools")->cascadeOnDelete();
            $table->foreign('standard_id')->references('id')->on('standards')->cascadeOnDelete();
            $table->timestamps();

            $table->uniqid(["school_id", "standard_id", "grade", "rank"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_grading_systems');
    }
};
