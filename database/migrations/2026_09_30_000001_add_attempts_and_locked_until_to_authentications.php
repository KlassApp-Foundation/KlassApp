<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authentications', function (Blueprint $table) {
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('authentications', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'locked_until']);
        });
    }
};
