<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_mcp_connectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('connector_type');
            $table->string('external_team_id');
            $table->string('external_team_name')->nullable();
            $table->text('credentials');
            $table->timestamp('token_expires_at')->nullable();
            $table->string('auth_mode')->default('oauth_remote');
            $table->string('status')->default('active');
            $table->string('trust_level')->default('first_party_catalog');
            $table->string('write_mode')->default('deny');
            $table->json('tool_allowlist')->nullable();
            $table->json('tool_denylist')->nullable();
            $table->unsignedInteger('connected_by')->nullable();
            $table->foreign('connected_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'connector_type', 'external_team_id'], 'school_mcp_unique');
            $table->index(['connector_type', 'status']);
            $table->index('token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_mcp_connectors');
    }
};
