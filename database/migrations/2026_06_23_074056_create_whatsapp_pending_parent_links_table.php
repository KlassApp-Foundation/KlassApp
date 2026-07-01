<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pending parent link requests from WhatsApp self-verification.
     *
     * When a parent texts a School Pay code that matches a student
     * but no parent is linked to that student yet, we create a pending
     * record here. The school admin reviews and approves these.
     */
    public function up(): void
    {
        Schema::create('whatsapp_pending_parent_links', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);                          // Parent's WhatsApp number
            $table->unsignedBigInteger('school_id');               // School from matched student
            $table->unsignedInteger('student_id')->nullable();   // Student user ID (matches users.id type)
            $table->string('student_payment_code', 50);            // The code they entered
            $table->string('student_name')->nullable();            // For display in admin panel
            $table->string('status', 20)->default('pending');      // pending | approved | rejected
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_pending_parent_links');
    }
};
