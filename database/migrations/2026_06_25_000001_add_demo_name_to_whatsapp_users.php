<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->string('demo_name', 100)->nullable()->after('student_payment_code');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->dropColumn('demo_name');
        });
    }
};
