<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_invites', function (Blueprint $table) {
            // Set when the invite email could not be queued; cleared on a successful retry.
            $table->timestamp('email_failed_at')->nullable()->after('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::table('school_invites', function (Blueprint $table) {
            $table->dropColumn('email_failed_at');
        });
    }
};
