<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('fee_category_id')->nullable();
            $table->unsignedInteger('user_id');          // student
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('payment_method', 50)->nullable();  // cash, cheque, mobile_money, bank_transfer
            $table->string('reference', 255)->nullable();      // cheque number, transaction ref
            $table->text('notes')->nullable();
            $table->unsignedInteger('recorded_by');         // admin/accountant who recorded it
            $table->string('status', 20)->default('paid');     // paid, refunded
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('fee_category_id')->references('id')->on('fees_categories')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('school_id');
            $table->index('user_id');
            $table->index('fee_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};
