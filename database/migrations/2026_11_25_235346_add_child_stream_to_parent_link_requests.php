<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parent_link_requests', function (Blueprint $table) {
            $table->string('child_stream', 50)->nullable()->default(null)->after('child_class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_link_requests', function (Blueprint $table) {
            $table->dropColumn('child_stream');
        });
    }
};
