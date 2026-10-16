<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How a parent asked to link (KlassApp number, form, or demo BIND), and the
 * WhatsApp profile name Meta sends with the message. Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parent_link_requests') && ! Schema::hasColumn('parent_link_requests', 'whatsapp_profile_name')) {
            Schema::table('parent_link_requests', function (Blueprint $table) {
                $table->string('whatsapp_profile_name', 150)->nullable()->after('parent_name');
            });
        }

        if (Schema::hasTable('parent_link_requests') && ! Schema::hasColumn('parent_link_requests', 'source')) {
            Schema::table('parent_link_requests', function (Blueprint $table) {
                $table->string('source', 32)->default('form')->after('flow_token');
            });
        }

        if (Schema::hasTable('whatsapp_users') && ! Schema::hasColumn('whatsapp_users', 'whatsapp_profile_name')) {
            Schema::table('whatsapp_users', function (Blueprint $table) {
                $table->string('whatsapp_profile_name', 150)->nullable()->after('demo_name');
            });
        }

        if (Schema::hasTable('parent_link_requests') && Schema::hasColumn('parent_link_requests', 'source')) {
            DB::table('parent_link_requests')->where('flow_token', 'demo-bind')->update(['source' => 'demo']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('parent_link_requests') && Schema::hasColumn('parent_link_requests', 'source')) {
            Schema::table('parent_link_requests', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }

        if (Schema::hasTable('parent_link_requests') && Schema::hasColumn('parent_link_requests', 'whatsapp_profile_name')) {
            Schema::table('parent_link_requests', function (Blueprint $table) {
                $table->dropColumn('whatsapp_profile_name');
            });
        }

        if (Schema::hasTable('whatsapp_users') && Schema::hasColumn('whatsapp_users', 'whatsapp_profile_name')) {
            Schema::table('whatsapp_users', function (Blueprint $table) {
                $table->dropColumn('whatsapp_profile_name');
            });
        }
    }
};
