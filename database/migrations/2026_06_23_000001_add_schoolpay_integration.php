<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add School Pay integration columns and transaction log table.
     *
     * - schools: school_pay_code, API password, webhook toggle
     * - whatsapp_users: student_payment_code for parent→student linking
     * - schoolpay_transactions: immutable log of every payment webhook received
     */
    public function up(): void
    {
        // ── Schools table ──────────────────
        Schema::table('schools', function (Blueprint $table) {
            $table->string('school_pay_code', 50)->nullable()->unique()->after('slug');
            $table->text('school_pay_api_password')->nullable()->after('school_pay_code');
            $table->boolean('school_pay_webhook_enabled')->default(false)->after('school_pay_api_password');
            $table->timestamp('schoolpay_last_sync_at')->nullable()->after('school_pay_webhook_enabled');
        });

        // ── WhatsApp Users table ───────────────────
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->string('student_payment_code', 50)->nullable()->after('school_id');
            $table->boolean('verified_via_schoolpay')->default(false)->after('student_payment_code');

            $table->index('student_payment_code');
        });

        // ── School Pay Transactions log ────────────────────
        Schema::create('schoolpay_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedInteger('student_id')->nullable();
            $table->string('student_payment_code', 50);
            $table->string('schoolpay_receipt')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('channel', 50)->nullable();
            $table->string('student_name')->nullable();
            $table->string('student_class')->nullable();
            $table->string('source', 20)->default('webhook'); // webhook | sync_poll
            $table->json('raw_payload')->nullable();
            $table->string('notification_status', 20)->default('pending'); // pending | sent | failed | skipped
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['school_id', 'student_payment_code']);
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schoolpay_transactions');

        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->dropIndex(['student_payment_code']);
            $table->dropColumn(['student_payment_code', 'verified_via_schoolpay']);
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropUnique(['school_pay_code']);
            $table->dropColumn([
                'school_pay_code',
                'school_pay_api_password',
                'school_pay_webhook_enabled',
                'schoolpay_last_sync_at',
            ]);
        });
    }
};
