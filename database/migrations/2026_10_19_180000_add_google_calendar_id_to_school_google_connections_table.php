<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_google_connections')) {
            return;
        }

        if (Schema::hasColumn('user_google_connections', 'google_calendar_id')) {
            return;
        }

        Schema::table('user_google_connections', function (Blueprint $table) {
            $table->string('google_calendar_id')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_google_connections')) {
            return;
        }

        if (! Schema::hasColumn('user_google_connections', 'google_calendar_id')) {
            return;
        }

        Schema::table('user_google_connections', function (Blueprint $table) {
            $table->dropColumn('google_calendar_id');
        });
    }
};
