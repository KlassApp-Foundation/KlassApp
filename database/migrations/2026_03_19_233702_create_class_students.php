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
        Schema::create('class_students', function (Blueprint $table) {
            //
            $table->id();
            $table->unsignedBigInteger("school_id");
            $table->unsignedInteger("standard_id");
            $table->unsignedInteger("student_id");

            $table->foreign("standard_id")->references("id")->on("standards")->cascadeOnDelete();
            $table->foreign("student_id")->references("id")->on("users")->cascadeOnDelete();
            $table->foreign("school_id")->references("id")->on("schools")->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_students', function (Blueprint $table) {
            //
        });
    }
};
