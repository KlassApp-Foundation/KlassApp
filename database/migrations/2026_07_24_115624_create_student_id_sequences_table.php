<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_id_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->primary();
            $table->unsignedInteger('next_seq')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_id_sequences');
    }
};