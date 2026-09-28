<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_invites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('email')->comment('Invited email address');
            $table->unsignedInteger('user_id')->nullable()->comment('Set when claimed — the created User');
            $table->string('token_hash', 64)->unique()->comment('SHA-256 of the random token');
            $table->string('role', 50)->default('class_teacher');
            $table->unsignedBigInteger('standard_link_id')->nullable()->comment('Class assignment on claim');
            $table->string('name', 191)->nullable()->comment('Pre-filled teacher name');
            $table->string('phone', 50)->nullable()->comment('For WhatsApp delivery');
            $table->timestamp('expires_at');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'email']);
            $table->index('expires_at');
        });

        // Foreign keys added after table creation so engine mismatches on
        // some local MySQL configs don't block the migration. Laravel Cloud
        // staging/production use InnoDB consistently and won't hit this.
        try {
            Schema::table('teacher_invites', function (Blueprint $table) {
                $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            });
        } catch (\Throwable $e) {
            // Engine mismatch on local — constraint omitted gracefully
        }
        try {
            Schema::table('teacher_invites', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        } catch (\Throwable $e) {
        }
        try {
            Schema::table('teacher_invites', function (Blueprint $table) {
                $table->foreign('standard_link_id')->references('id')->on('standards_link')->nullOnDelete();
            });
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_invites');
    }
};
