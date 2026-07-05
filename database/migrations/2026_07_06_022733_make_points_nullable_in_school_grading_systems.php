<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_grading_systems', function (Blueprint $table) {
            $table->integer('points')->nullable()->change();
            $table->integer('min_score')->nullable()->change();
            $table->integer('max_score')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('school_grading_systems', function (Blueprint $table) {
            $table->integer('points')->nullable(false)->change();
            $table->integer('min_score')->nullable(false)->change();
            $table->integer('max_score')->nullable(false)->change();
        });
    }
};
