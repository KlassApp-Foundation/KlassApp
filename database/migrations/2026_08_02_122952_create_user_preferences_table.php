<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('preferred_language', 8)->default('en');
            $table->string('notification_channel', 16)->default('whatsapp');
            $table->boolean('digest_enabled')->default(false);
            $table->string('digest_frequency', 16)->default('none');
            $table->unsignedTinyInteger('digest_weekday')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
