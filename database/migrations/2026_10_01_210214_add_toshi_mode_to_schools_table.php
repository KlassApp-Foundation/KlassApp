<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('toshi_mode', 32)->default('preview')->after('toshi_enabled');
        });

        // Schools that already had early-access assistant stay on assistant;
        // everyone else defaults to preview for the soft launch.
        DB::table('schools')->where('toshi_enabled', 1)->update(['toshi_mode' => 'assistant']);
        DB::table('schools')->where('toshi_enabled', 0)->update(['toshi_mode' => 'preview']);
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('toshi_mode');
        });
    }
};
