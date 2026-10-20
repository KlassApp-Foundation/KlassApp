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

        if (! Schema::hasColumn('user_google_connections', 'last_error')) {
            Schema::table('user_google_connections', function (Blueprint $table) {
                $table->string('last_error')->nullable();
            });
        }

        if (! Schema::hasColumn('user_google_connections', 'attention_scopes')) {
            Schema::table('user_google_connections', function (Blueprint $table) {
                $table->json('attention_scopes')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_google_connections')) {
            return;
        }

        Schema::table('user_google_connections', function (Blueprint $table) {
            if (Schema::hasColumn('user_google_connections', 'attention_scopes')) {
                $table->dropColumn('attention_scopes');
            }

            if (Schema::hasColumn('user_google_connections', 'last_error')) {
                $table->dropColumn('last_error');
            }
        });
    }
};
