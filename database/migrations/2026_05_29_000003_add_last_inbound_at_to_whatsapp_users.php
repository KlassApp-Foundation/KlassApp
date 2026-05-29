<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->timestamp('last_inbound_at')->nullable()->after('opted_in');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->dropColumn('last_inbound_at');
        });
    }
};
