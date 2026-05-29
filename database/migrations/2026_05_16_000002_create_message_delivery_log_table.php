<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tracks delivery status and costs for all outbound WhatsApp messages.
     */
    public function up(): void
    {
        Schema::create('message_delivery_log', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp_message_id', 100)->nullable();
            $table->string('phone', 20);
            $table->string('template_name', 100)->nullable();
            $table->enum('direction', ['inbound', 'outbound'])->default('outbound');
            $table->enum('category', ['service', 'utility', 'marketing', 'authentication'])->nullable();
            $table->enum('status', ['sent', 'delivered', 'read', 'failed'])->default('sent');
            $table->decimal('cost_usd', 8, 6)->default(0);
            $table->text('content_preview')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('flow_type', 50)->nullable(); // 'grades', 'attendance', 'fee_reminder', etc.
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();

            $table->index(['phone', 'status']);
            $table->index(['template_name', 'sent_at']);
            $table->index(['flow_type', 'sent_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_delivery_log');
    }
};
