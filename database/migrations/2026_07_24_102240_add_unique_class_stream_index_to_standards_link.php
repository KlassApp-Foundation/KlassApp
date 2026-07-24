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
            $table->unique(['school_id', 'section_id', 'academic_year_id', 'stream'], 'standards_link_unique_class_stream');
        });
    }

    public function down(): void
    {
        Schema::table('standards_link', function (Blueprint $table) {
            $table->dropUnique('standards_link_unique_class_stream');
        });
    }
};
