<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users.is_public_demo marks the live demo's admin account, whose password is
 * shared in public. PublicDemoLockdown keeps that account inside its demo
 * school (no profile, password, mail, uploads or outside actions).
 * Set by demo:provision. Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_public_demo')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_public_demo')->default(false);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'is_public_demo')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_public_demo');
        });
    }
};
