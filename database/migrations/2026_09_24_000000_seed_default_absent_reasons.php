<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A school cannot record attendance without at least one absence reason, because the entry
 * form requires one. The reasons existed only in AbsentReasonsTableSeeder, and deploys run
 * migrations rather than seeders, so a deployed environment could have zero reasons and be
 * unable to complete any attendance entry (found on staging: 0 rows).
 *
 * Seeds the same sensible defaults the seeder uses, idempotently and keyed on the title, so
 * it is safe to run repeatedly and safe on an environment that already has them.
 */
return new class extends Migration
{
    private const DEFAULT_REASONS = ['Health Issue', 'Family Functions', 'Personal Work', 'Others'];

    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('absent_reasons')) {
            return;
        }

        foreach (self::DEFAULT_REASONS as $title) {
            $exists = DB::table('absent_reasons')->where('title', $title)->exists();

            if (! $exists) {
                DB::table('absent_reasons')->insert([
                    'title' => $title,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Intentionally empty: attendance rows reference these reasons, so removing them on
        // rollback could break existing records. They are harmless defaults to leave in place.
    }
};
