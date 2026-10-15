<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The demo student a number follows after "BIND <code>". Null for every
 * other chat. demo:expire-chats clears it with the rest of the demo link.
 * Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('whatsapp_users', 'demo_student_id')) {
            return;
        }

        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->unsignedInteger('demo_student_id')->nullable()->after('demo_linked_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('whatsapp_users', 'demo_student_id')) {
            return;
        }

        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->dropColumn('demo_student_id');
        });
    }
};
