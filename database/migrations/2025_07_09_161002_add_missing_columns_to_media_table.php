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
        Schema::table('media', function (Blueprint $table) {
            if (!Schema::hasColumn('media', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('media', 'conversions_disk')) {
                $table->string('conversions_disk')->nullable()->after('disk');
            }
            if (!Schema::hasColumn('media', 'generated_conversions')) {
                $table->json('generated_conversions')->nullable()->after('custom_properties');
            }
            if (!Schema::hasColumn('media', 'responsive_images')) {
                $table->json('responsive_images')->nullable()->after('generated_conversions');
            }
            if (!Schema::hasColumn('media', 'manipulations')) {
                $table->json('manipulations')->nullable()->after('responsive_images');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn([
                'conversions_disk',
                'generated_conversions',
                'responsive_images',
                'manipulations',
            ]);
        });
    }
};
