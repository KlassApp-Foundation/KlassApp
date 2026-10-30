<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6d: the per-release WhatsApp switch.
 *
 * A release records whether its marks may go out on WhatsApp. Off by default:
 * a release still happens, opens the portal, Toshi and the report cards, and
 * sends nothing. A school admin turns it on for that release only.
 *
 * Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marks_releases') || Schema::hasColumn('marks_releases', 'whatsapp_send')) {
            return;
        }

        Schema::table('marks_releases', function (Blueprint $table): void {
            $table->boolean('whatsapp_send')->default(false)->after('result');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('marks_releases') || ! Schema::hasColumn('marks_releases', 'whatsapp_send')) {
            return;
        }

        Schema::table('marks_releases', function (Blueprint $table): void {
            $table->dropColumn('whatsapp_send');
        });
    }
};
