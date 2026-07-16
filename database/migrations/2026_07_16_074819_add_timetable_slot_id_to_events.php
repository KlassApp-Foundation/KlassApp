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
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('timetable_slot_id')->nullable()->after('exam_id');
            $table->foreign('timetable_slot_id')->references('id')->on('timetable_slots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['timetable_slot_id']);
            $table->dropColumn('timetable_slot_id');
        });
    }
};
