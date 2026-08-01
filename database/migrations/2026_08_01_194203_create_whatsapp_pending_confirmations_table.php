<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_pending_confirmations', function (Blueprint $table) {
            $table->id();
            $table->string('token', 16)->unique();
            $table->string('phone', 20)->index();
            $table->unsignedInteger('user_id');
            $table->string('mechanism', 20); // approvable | tier2
            $table->string('conversation_id', 36)->nullable()->index();
            $table->string('approval_id', 64)->nullable();
            $table->string('agent_class')->nullable();
            $table->json('payload')->nullable();
            $table->string('outbound_wamid')->nullable();
            $table->text('preview')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending|approved|rejected|expired
            $table->timestamp('expires_at')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['phone', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_pending_confirmations');
    }
};
