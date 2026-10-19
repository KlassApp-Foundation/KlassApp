<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_invites', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('school_name');
            $table->string('contact_name')->nullable();
            $table->string('country')->nullable();
            $table->unsignedInteger('approx_students')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_invites');
    }
};
