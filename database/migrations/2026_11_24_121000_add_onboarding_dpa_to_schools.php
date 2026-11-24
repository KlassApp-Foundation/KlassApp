<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DPA acceptance, recorded on the school.
 *
 * The checklist workbook's Confirmations tab was the only place the data
 * processing agreement was checked, and only the converter read it. The engine
 * needs its own record so every onboarding write can require it.
 *
 * required_at is stamped when a real school starts onboarding. A school that
 * predates this and never starts onboarding is not retroactively blocked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->timestamp('onboarding_dpa_required_at')->nullable()->after('onboarding_skipped_steps');
            $table->timestamp('onboarding_dpa_confirmed_at')->nullable()->after('onboarding_dpa_required_at');
            $table->string('onboarding_dpa_version', 40)->nullable()->after('onboarding_dpa_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['onboarding_dpa_required_at', 'onboarding_dpa_confirmed_at', 'onboarding_dpa_version']);
        });
    }
};
