<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Maps WhatsApp phone numbers to KlassApp users (parents, teachers, admins).
     */
    public function up(): void
    {
        Schema::create('whatsapp_users', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique(); // E.164 format, e.g. +256701234567
            $table->unsignedInteger('user_id');
            $table->enum('user_type', ['parent', 'teacher', 'admin']);
            $table->timestamp('verified_at')->nullable();
            $table->boolean('opted_in')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_type', 'opted_in']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_users');
    }
};
