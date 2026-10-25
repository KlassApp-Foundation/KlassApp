<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('school_invites', 'early_access_request_id')) {
            return;
        }

        Schema::table('school_invites', function (Blueprint $table) {
            // Nullable so invites that already exist keep working. New invites
            // store the request they were issued from. early_access_requests.id
            // is bigint unsigned ($table->id()), so this column matches it.
            $table->foreignId('early_access_request_id')
                ->nullable()
                ->constrained('early_access_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('school_invites', 'early_access_request_id')) {
            return;
        }

        Schema::table('school_invites', function (Blueprint $table) {
            $table->dropForeign(['early_access_request_id']);
            $table->dropColumn('early_access_request_id');
        });
    }
};
