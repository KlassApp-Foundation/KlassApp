<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_google_connections')) {
            return;
        }

        Schema::create('school_google_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->string('google_sub')->nullable();
            $table->string('google_email')->nullable();
            // Encrypted JSON (access_token, refresh_token, token_type). Null after disconnect.
            $table->text('credentials')->nullable();
            $table->json('granted_scopes')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('status')->default('active');
            // users.id is increments (unsigned int), not a bigint.
            $table->unsignedInteger('connected_by')->nullable();
            $table->foreign('connected_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamps();

            $table->unique('school_id');
            $table->index('token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_google_connections');
    }
};
