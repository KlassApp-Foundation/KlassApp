<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standards', function (Blueprint $table) {
            $table->enum('grading_style', ['aggregate', 'total_marks'])->nullable()->after('status');
        });

        Schema::table('standards_link', function (Blueprint $table) {
            $table->string('sub_group')->nullable()->after('stream');
        });
    }

    public function down(): void
    {
        Schema::table('standards_link', function (Blueprint $table) {
            $table->dropColumn('sub_group');
        });

        Schema::table('standards', function (Blueprint $table) {
            $table->dropColumn('grading_style');
        });
    }
};
