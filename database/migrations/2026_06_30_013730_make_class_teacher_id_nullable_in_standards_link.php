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
        Schema::table('standards_link', function (Blueprint $table) {
            $table->integer('class_teacher_id')->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('standards_link', function (Blueprint $table) {
            $table->integer('class_teacher_id')->unsigned()->nullable(false)->change();
        });
    }
};
