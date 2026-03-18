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
        Schema::create('school_subject', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("school_id");
            $table->unsignedInteger("subject_id");
            $table->foreign("school_id")->references("id")->on("schools")->cascadeOnDelete();
            $table->foreign("subject_id")->references("id")->on("subjects")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_subject');
    }
};
