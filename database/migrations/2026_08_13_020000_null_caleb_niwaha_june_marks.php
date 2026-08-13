<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marks', function (Blueprint $table) {
            $table->decimal('marks', 5, 2)->nullable()->change();
        });

        DB::table('marks')
            ->where('school_id', 104)
            ->where('student_id', 2673)
            ->where('exam_id', 22)
            ->update(['marks' => null]);
    }

    public function down(): void
    {
        Schema::table('marks', function (Blueprint $table) {
            $table->decimal('marks', 5, 2)->nullable(false)->change();
        });
    }
};
