<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable(false)->change();
        });

        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
