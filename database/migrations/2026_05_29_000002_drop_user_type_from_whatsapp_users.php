<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            // Drop the composite index first
            $table->dropIndex(['user_type', 'opted_in']);
            // Drop the column
            $table->dropColumn('user_type');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->enum('user_type', ['parent', 'teacher', 'admin'])->after('user_id');
            $table->index(['user_type', 'opted_in']);
        });
    }
};
