<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('schools', 'state_id')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->unsignedInteger('state_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        // The original schools table owns this column. Do not drop it.
    }
};
