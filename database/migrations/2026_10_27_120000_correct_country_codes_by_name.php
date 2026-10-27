<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The original country seed stored another country's short name and ISO code
 * on these rows (Uganda was AF/AFG, DRC was MA/MYS). Correct them by name.
 * Safe to run twice. Rows whose name is not in this list are left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $codes = self::codes();
        $targets = [];

        foreach ($codes as $name => $spec) {
            $row = DB::table('countries')->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            if ($row === null) {
                continue;
            }
            $targets[$name] = [
                'id' => $row->id,
                'short_name' => $row->short_name,
            ];
        }

        foreach ($targets as $name => $target) {
            DB::table('countries')->where('id', $target['id'])->update([
                'short_name' => 'T'.$target['id'],
            ]);
        }

        foreach ($targets as $name => $target) {
            [$short, $iso, $tel] = $codes[$name];
            $taken = DB::table('countries')
                ->where('short_name', $short)
                ->where('id', '!=', $target['id'])
                ->exists();
            if ($taken) {
                DB::table('countries')->where('id', $target['id'])->update([
                    'short_name' => $target['short_name'],
                ]);

                continue;
            }

            DB::table('countries')->where('id', $target['id'])->update([
                'short_name' => $short,
                'iso_code' => $iso,
                'tel_prefix' => $tel,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // The previous codes were another country's. Do not put them back.
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function codes(): array
    {
        return [
            'Uganda' => ['UG', 'UGA', '256'],
            'Kenya' => ['KE', 'KEN', '254'],
            'Tanzania' => ['TZ', 'TZA', '255'],
            'Rwanda' => ['RW', 'RWA', '250'],
            'Burundi' => ['BI', 'BDI', '257'],
            'South Sudan' => ['SS', 'SSD', '211'],
            'DRC' => ['CD', 'COD', '243'],
            'Nigeria' => ['NG', 'NGA', '234'],
            'Egypt' => ['EG', 'EGY', '20'],
            'Other' => ['OT', 'OTH', ''],
        ];
    }
};
