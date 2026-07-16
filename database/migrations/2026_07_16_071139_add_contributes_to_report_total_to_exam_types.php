<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_types', function (Blueprint $table) {
            $table->boolean('contributes_to_report_total')->default(false)->after('code');
        });

        // Sensible defaults: EOT and FINAL contribute to term totals;
        // BOT, MID, MOCK, PreMOCK, WE, ME are diagnostic/practice by default.
        // Admins can override per school via the exam-type settings UI.
        DB::table('exam_types')->whereIn('code', ['EOT', 'FINAL'])->update(['contributes_to_report_total' => true]);
    }

    public function down(): void
    {
        Schema::table('exam_types', function (Blueprint $table) {
            $table->dropColumn('contributes_to_report_total');
        });
    }
};
