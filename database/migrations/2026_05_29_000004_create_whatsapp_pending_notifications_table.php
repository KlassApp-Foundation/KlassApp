<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_pending_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_user_id')->constrained('whatsapp_users')->cascadeOnDelete();
            $table->string('flow_type', 50);
            $table->text('message')->nullable();
            $table->string('template_name', 100)->nullable();
            $table->json('template_variables')->nullable();
            $table->timestamp('send_after')->nullable()->comment('null = send ASAP when window opens');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['whatsapp_user_id', 'send_after']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_pending_notifications');
    }
};
