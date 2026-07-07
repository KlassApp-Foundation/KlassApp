<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emis_schools', function (Blueprint $table) {
            $table->id();
            $table->string('emis_code', 20)->unique();
            $table->string('school_name', 255);
            $table->string('district', 100)->nullable();
            $table->string('ownership', 50)->nullable();
            $table->string('school_type', 50)->nullable();
            $table->string('status', 20)->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamps();

            $table->index('district');
            $table->index('school_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emis_schools');
    }
};
