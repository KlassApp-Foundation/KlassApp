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
        Schema::create('student_promotion_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("school_id");
            $table->unsignedInteger("standard_id"); 
            $table->unsignedInteger('section_id');
            $table->enum('rule_type', ['average', 'aggregate', 'points']);
            $table->decimal("min_average", 5,  2)->nullable();
            $table->string("min_aggregate")->nullable();
            $table->integer("min_points")->nullable();

            $table->foreign("school_id")->references("id")->on("schools")->cascadeOnDelete();
            $table->foreign('standard_id')->references('id')->on('standards')->cascadeOnDelete();
            $table->foreign("section_id")->references("id")->on("sections")->cascadeOnDelete();
            $table->timestamps();

            $table->unique(["section_id", "min_average"]);
            $table->unique(["section_id", "min_aggregate"]);
            $table->unique(["section_id", "min_points"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_promotion_rules');
    }
};
