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
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("school_id");
            $table->unsignedInteger("academic_year_id");
            $table->string("name");
            $table->dateTime("starts_on")->nullable();
            $table->dateTime("ends_on")->nullable();
            $table->foreign("school_id")->references("id")->on("schools")->cascadeOnDelete();
            $table->foreign("academic_year_id")->references("id")->on("academic_years")->cascadeOnDelete();

            $table->timestamps();
            $table->unique(["school_id", "name"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_terms');
    }
};
