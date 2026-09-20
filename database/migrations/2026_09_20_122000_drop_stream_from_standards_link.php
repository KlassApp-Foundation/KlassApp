<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('standards_link', 'stream')) {
            Schema::table('standards_link', function (Blueprint $table): void {
                $table->dropColumn('stream');
            });
        }
    }

    public function down(): void
    {
        Schema::table('standards_link', function (Blueprint $table): void {
            $table->string('stream')->nullable()->after('no_of_students');
        });
    }
};
