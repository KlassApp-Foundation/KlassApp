<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plans') && ! Schema::hasColumn('plans', 'is_platform_only')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->boolean('is_platform_only')->default(false);
            });
        }

        if (! Schema::hasTable('plans')) {
            return;
        }

        // A fresh install has no plan rows yet: PlansTableSeeder/plans:ensure
        // creates the full set after migrations. An existing install already
        // has Freemium/Growth/Premium, so add the platform-only tier here.
        if (! DB::table('plans')->exists()) {
            return;
        }

        // The platform admin can assign this to a founding school. It is not
        // shown in the school's own plan picker: see is_platform_only filters.
        DB::table('plans')->updateOrInsert(
            ['name' => 'early_adopter'],
            [
                'display_name' => 'Early adopter',
                'cycle' => 30,
                'order' => 0,
                'is_active' => 1,
                'is_custom_pricing' => 0,
                'is_platform_only' => 1,
                'amount' => 0,
                'no_of_users' => 0,
                'no_of_students' => 0,
                'whatsapp_services' => null,
                'no_of_events' => 999999,
                'no_of_folders' => 999999,
                'no_of_files' => 999999,
                'no_of_videos' => 999999,
                'no_of_audios' => 999999,
                'no_of_bulletins' => 999999,
                'no_of_groups' => 999999,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('plans')) {
            // Never leave a formerly platform-only tier selectable by schools.
            DB::table('plans')->where('name', 'early_adopter')->update(['is_active' => 0]);
        }

        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'is_platform_only')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('is_platform_only');
            });
        }
    }
};
