<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Add computed statutory deduction columns (nullable for legacy rows)
            $table->decimal('gross_pay', 14, 2)->nullable()->after('comments');
            $table->decimal('paye', 14, 2)->nullable()->after('gross_pay');
            $table->decimal('nssf', 14, 2)->nullable()->after('paye');
            $table->decimal('lst', 14, 2)->nullable()->after('nssf');
            $table->decimal('net_pay', 14, 2)->nullable()->after('lst');

            // Unique constraint: prevent duplicate payroll for same staff + period
            // A staff member can only have one payroll record per date range.
            $table->unique(['staff_id', 'start_date', 'end_date'], 'payrolls_staff_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropUnique('payrolls_staff_period_unique');
            $table->dropColumn(['gross_pay', 'paye', 'nssf', 'lst', 'net_pay']);
        });
    }
};
