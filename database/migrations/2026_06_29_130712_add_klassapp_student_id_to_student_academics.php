<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_academics', function (Blueprint $table) {
            $table->string('klassapp_student_id', 20)->nullable()->unique()->after('std_school_pay_number');
            $table->index('klassapp_student_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_academics', function (Blueprint $table) {
            $table->dropIndex(['klassapp_student_id']);
            $table->dropColumn('klassapp_student_id');
        });
    }
};
