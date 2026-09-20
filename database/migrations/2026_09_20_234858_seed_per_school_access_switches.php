<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Per-school access switches (login_status, maintenance) move from the single
 * global `settings` row into `school_details` (school_id + meta_key/meta_value),
 * matching the existing per-school config pattern.
 *
 * Transition behaviour: each existing school is seeded with the CURRENT global
 * value (falling back to enabled) so no school is silently reset to blocked.
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $globalLogin = DB::table('settings')->where('key', 'login_status')->value('value');
        $globalMaint = DB::table('settings')->where('key', 'maintenance')->value('value');

        $login = ($globalLogin === null || $globalLogin === '') ? '1' : (string) $globalLogin; // default: enabled
        $maint = ($globalMaint === null || $globalMaint === '') ? '0' : (string) $globalMaint;

        $now = now();
        foreach (DB::table('schools')->pluck('id') as $schoolId) {
            foreach (['login_status' => $login, 'maintenance' => $maint] as $key => $value) {
                $exists = DB::table('school_details')
                    ->where('school_id', $schoolId)->where('meta_key', $key)->exists();
                if (! $exists) {
                    DB::table('school_details')->insert([
                        'school_id'  => $schoolId,
                        'meta_key'   => $key,
                        'meta_value' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('school_details')->whereIn('meta_key', ['login_status', 'maintenance'])->delete();
    }
};
