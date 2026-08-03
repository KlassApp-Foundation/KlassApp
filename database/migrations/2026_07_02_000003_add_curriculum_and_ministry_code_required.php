<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Nullable so SaaS signup can leave curriculum unanswered for Toshi.
            $table->string('curriculum', 50)->nullable()->default(null)->after('ministry_code');
            $table->index('curriculum');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropIndex(['curriculum']);
            $table->dropColumn('curriculum');
        });
    }
};
