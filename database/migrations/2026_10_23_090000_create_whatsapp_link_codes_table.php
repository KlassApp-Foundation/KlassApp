<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Short codes that link a WhatsApp number to the signed-in person. The code is
 * shown on screen (and inside a wa.me link); only its HMAC is stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_link_codes')) {
            return;
        }

        Schema::create('whatsapp_link_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('user_id');
            $table->char('code_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'used_at']);
            $table->index('code_hash');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_link_codes');
    }
};
