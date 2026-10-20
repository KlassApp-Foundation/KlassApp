<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_google_connections')) {
            Schema::create('user_google_connections', function (Blueprint $table) {
                $table->id();
                // users.id is increments (unsigned int), not a bigint.
                $table->unsignedInteger('user_id');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->unsignedBigInteger('school_id');
                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->string('google_sub')->nullable();
                $table->string('google_email')->nullable();
                // Encrypted JSON (access_token, refresh_token, token_type). Null after Google revokes it.
                $table->text('credentials')->nullable();
                $table->json('granted_scopes')->nullable();
                $table->json('attention_scopes')->nullable();
                $table->string('last_error')->nullable();
                $table->timestamp('token_expires_at')->nullable();
                $table->string('status')->default('active');
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('last_refreshed_at')->nullable();
                $table->timestamps();

                $table->unique('user_id');
                $table->index(['school_id', 'status']);
                $table->index('token_expires_at');
            });
        }

        if (! Schema::hasTable('school_google_connector_allows')) {
            Schema::create('school_google_connector_allows', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                $table->string('connector', 32);
                $table->boolean('allowed')->default(false);
                $table->timestamps();

                $table->unique(['school_id', 'connector']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_google_connector_allows');
        Schema::dropIfExists('user_google_connections');
    }
};
