<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class-teacher self-setup: single-use personal links, an optional school-wide
 * staff link, and the queue of people who used the staff link without being on
 * the teacher list.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_setup_tokens')) {
            Schema::create('teacher_setup_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedInteger('user_id');
                $table->char('token_hash', 64)->unique();
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'used_at']);
                $table->index('expires_at');
            });
        }

        if (! Schema::hasTable('school_staff_links')) {
            Schema::create('school_staff_links', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->char('token_hash', 64)->unique();
                $table->timestamp('expires_at');
                $table->timestamp('revoked_at')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'revoked_at']);
            });
        }

        if (! Schema::hasTable('staff_link_requests')) {
            Schema::create('staff_link_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('status', 20)->default('pending');
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_link_requests');
        Schema::dropIfExists('school_staff_links');
        Schema::dropIfExists('teacher_setup_tokens');
    }
};
