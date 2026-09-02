<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_link_requests', function (Blueprint $table) {
            $table->string('school_name', 255)->nullable()->after('child_class');
        });
    }

    public function down(): void
    {
        Schema::table('parent_link_requests', function (Blueprint $table) {
            $table->dropColumn('school_name');
        });
    }
};
