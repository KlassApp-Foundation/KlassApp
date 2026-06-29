<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('ministry_code', 20)->nullable()->unique()->after('slug');
            $table->index('ministry_code');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropIndex(['ministry_code']);
            $table->dropColumn('ministry_code');
        });
    }
};
