<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Null means the automatic rule: rank by aggregate when the class has
 * grade points, otherwise by the mark total. A stored value is a choice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (! Schema::hasColumn('schools', 'ranking_basis')) {
                $table->string('ranking_basis', 16)->nullable()->after('report_kind');
            }
        });
        Schema::table('standards', function (Blueprint $table): void {
            if (! Schema::hasColumn('standards', 'ranking_basis')) {
                $table->string('ranking_basis', 16)->nullable()->after('grading_style');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (Schema::hasColumn('schools', 'ranking_basis')) {
                $table->dropColumn('ranking_basis');
            }
        });
        Schema::table('standards', function (Blueprint $table): void {
            if (Schema::hasColumn('standards', 'ranking_basis')) {
                $table->dropColumn('ranking_basis');
            }
        });
    }
};
