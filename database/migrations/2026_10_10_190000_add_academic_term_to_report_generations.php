<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_generations', function (Blueprint $table) {
            if (! Schema::hasColumn('report_generations', 'academic_term_id')) {
                $table->unsignedBigInteger('academic_term_id')->nullable()->after('standard_link_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_generations', function (Blueprint $table) {
            if (Schema::hasColumn('report_generations', 'academic_term_id')) {
                $table->dropColumn('academic_term_id');
            }
        });
    }
};
