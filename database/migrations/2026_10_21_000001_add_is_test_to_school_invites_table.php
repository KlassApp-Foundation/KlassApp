<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('school_invites', 'is_test')) {
            return;
        }

        Schema::table('school_invites', function (Blueprint $table) {
            // Set by a platform admin on the early-access invite form.
            // Default false so existing invites stay real schools. Dropped on rollback.
            $table->boolean('is_test')->default(false);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('school_invites', 'is_test')) {
            return;
        }

        Schema::table('school_invites', function (Blueprint $table) {
            $table->dropColumn('is_test');
        });
    }
};
