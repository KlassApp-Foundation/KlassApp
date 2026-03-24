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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->integer("position");
            $table->string("status")->default("1");
            $table->unsignedBigInteger("school_id");
            $table->foreign("school_id")->references("id")->on("schools")->cascadeOnDelete();
            $table->unsignedInteger("standard_id"); 
            $table->foreign('standard_id')->references('id')->on('standards')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
