<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A durable record of each confirmed onboarding write.
 *
 * Confirmation used to live only in Livewire state and the session, so once the
 * panel reloaded there was no evidence of who approved what. One row per
 * confirmed step: the plan it belonged to (when there is one), the step key, the
 * person, a hash of the previewed arguments, and what the step actually applied.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_plan_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('plan_id')->nullable()->index();
            $table->string('step', 120);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('preview_hash', 64);
            $table->json('applied_counts')->nullable();
            $table->timestamp('confirmed_at');
            $table->timestamps();

            $table->index(['school_id', 'confirmed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_plan_steps');
    }
};
