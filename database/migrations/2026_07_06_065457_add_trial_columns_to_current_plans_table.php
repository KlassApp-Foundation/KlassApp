<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('current_plans', function (Blueprint $table) {
            $table->boolean('is_trial')->default(false)->after('plan_id');
            $table->timestamp('trial_started_at')->nullable()->after('is_trial');
            $table->timestamp('trial_ends_at')->nullable()->after('trial_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('current_plans', function (Blueprint $table) {
            $table->dropColumn(['is_trial', 'trial_started_at', 'trial_ends_at']);
        });
    }
};
