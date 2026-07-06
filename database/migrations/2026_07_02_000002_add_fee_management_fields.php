<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── fees_categories: installments, due dates, discounts, penalties ──
        Schema::table('fees_categories', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('amount');
            $table->boolean('enable_installments')->default(false)->after('due_date');
            $table->unsignedTinyInteger('max_installments')->nullable()->after('enable_installments');
            $table->decimal('deposit_required', 12, 2)->nullable()->after('max_installments');
            $table->decimal('late_fee_percent', 5, 2)->nullable()->after('deposit_required');
            $table->decimal('late_fee_flat', 12, 2)->nullable()->after('late_fee_percent');
            $table->unsignedSmallInteger('grace_days')->default(0)->after('late_fee_flat');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('grace_days');
            $table->decimal('discount_flat', 12, 2)->nullable()->after('discount_percent');
            $table->date('discount_until')->nullable()->after('discount_flat');
            $table->string('fee_type', 30)->default('tuition')->after('discount_until');
        });

        // ── schoolpay_transactions: reconciliation columns ──
        Schema::table('schoolpay_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('matched_fee_category_id')->nullable()->after('notification_status');
            $table->timestamp('reconciled_at')->nullable()->after('matched_fee_category_id');
            $table->foreign('matched_fee_category_id')
                ->references('id')->on('fees_categories')
                ->nullOnDelete();
            $table->index('reconciled_at');
        });
    }

    public function down(): void
    {
        Schema::table('schoolpay_transactions', function (Blueprint $table) {
            $table->dropForeign(['matched_fee_category_id']);
            $table->dropIndex(['reconciled_at']);
            $table->dropColumn(['matched_fee_category_id', 'reconciled_at']);
        });

        Schema::table('fees_categories', function (Blueprint $table) {
            $table->dropColumn([
                'due_date',
                'enable_installments',
                'max_installments',
                'deposit_required',
                'late_fee_percent',
                'late_fee_flat',
                'grace_days',
                'discount_percent',
                'discount_flat',
                'discount_until',
                'fee_type',
            ]);
        });
    }
};
