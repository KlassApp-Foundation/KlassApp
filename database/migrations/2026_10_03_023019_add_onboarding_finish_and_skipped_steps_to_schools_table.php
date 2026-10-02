<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->timestamp('onboarding_finished_at')->nullable()->after('toshi_mode');
            $table->json('onboarding_skipped_steps')->nullable()->after('onboarding_finished_at');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['onboarding_finished_at', 'onboarding_skipped_steps']);
        });
    }
};
