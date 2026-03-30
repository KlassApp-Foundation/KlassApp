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
        Schema::create('fees_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("school_id");
            $table->unsignedInteger("standard_id");
            $table->unsignedInteger('section_id')->nullable(); 
            $table->unsignedBigInteger("academic_term_id")->nullable();
            $table->string("name");
            $table->decimal("amount", 10, 2)->default(0.00);
            $table->foreign("standard_id")->references("id")->on("standards")->cascadeOnDelete();
            $table->foreign("school_id")->references("id")->on("schools")->cascadeOnDelete();
            $table->foreign("section_id")->references("id")->on("sections")->nullOnDelete();
            $table->foreign("academic_term_id")->references("id")->on("academic_terms")->nullOnDelete();

            $table->timestamps();
            $table->unique(["school_id", "standard_id", "section_id", "name"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees_categories');
    }
};
