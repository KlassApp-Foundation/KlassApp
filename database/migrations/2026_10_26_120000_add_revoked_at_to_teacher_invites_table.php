<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('teacher_invites', 'revoked_at')) {
            return;
        }

        Schema::table('teacher_invites', function (Blueprint $table) {
            $table->timestamp('revoked_at')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('teacher_invites', 'revoked_at')) {
            return;
        }

        Schema::table('teacher_invites', function (Blueprint $table) {
            $table->dropColumn('revoked_at');
        });
    }
};
