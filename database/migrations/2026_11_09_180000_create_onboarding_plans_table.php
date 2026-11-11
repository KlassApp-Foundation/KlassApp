<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->char('source_sha256', 64);
            $table->string('source_name');
            $table->unsignedTinyInteger('schema')->default(1);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('dpa_confirmed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->longText('payload');
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_plans');
    }
};
