<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('classes');
    }

    public function down(): void
    {
        Schema::create('classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('position');
            $table->string('status')->default('1');
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedInteger('standard_id');
            $table->foreign('standard_id')->references('id')->on('standards')->cascadeOnDelete();
            $table->timestamps();
        });
    }
};