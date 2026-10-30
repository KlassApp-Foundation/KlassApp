<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The original country seed stored another country's short name and ISO code
 * on these rows (Uganda was AF/AFG, DRC was MA/MYS). Correct them by name.
 * Safe to run twice. Rows whose name is not in this list are left alone.
 *
 * The data changes run in one transaction, so a failure leaves no row parked
 * on a temporary short name. This migration has no schema change. On MySQL a
 * DDL statement commits the current transaction implicitly, so any schema
 * change has to stay outside this transaction.
 *
 * down() is empty. The original codes are not recoverable and the correction
 * is not reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Data only. No DDL. MySQL would commit a schema change implicitly,
        // which would break the all-or-nothing guarantee below.
        $outcome = DB::transaction(fn (): array => $this->correct());

        foreach ($outcome['skipped'] as $skip) {
            $context = [
                'countries_id' => $skip['id'],
                'reason' => $skip['reason'],
            ];
            if (array_key_exists('blocking_countries_id', $skip)) {
                $context['blocking_countries_id'] = $skip['blocking_countries_id'];
            }
            if ($skip['id'] === null) {
                $context['name'] = $skip['name'];
            }

            Log::warning('Country code correction skipped a row.', $context);
        }

        Log::info('Country code correction finished.', [
            'corrected' => $outcome['corrected'],
            'skipped' => count($outcome['skipped']),
        ]);
    }

    /**
     * Empty on purpose. The original codes are not recoverable and the
     * correction is not reversible.
     */
    public function down(): void
    {
    }

    /**
     * @return array{corrected: int, skipped: list<array{id: int|null, reason: string, name?: string, blocking_countries_id?: int}>}
     */
    private function correct(): array
    {
        $codes = self::codes();
        $targets = [];
        $skipped = [];

        foreach ($codes as $name => $spec) {
            $row = DB::table('countries')->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            if ($row === null) {
                $skipped[] = [
                    'id' => null,
                    'reason' => 'no countries row matched this name',
                    'name' => $name,
                ];

                continue;
            }

            $targets[$name] = [
                'id' => (int) $row->id,
                'short_name' => $row->short_name,
            ];
        }

        foreach ($targets as $target) {
            DB::table('countries')->where('id', $target['id'])->update([
                'short_name' => 'T'.$target['id'],
            ]);
        }

        $corrected = 0;

        foreach ($targets as $name => $target) {
            [$short, $iso, $tel] = $codes[$name];
            $blocker = DB::table('countries')
                ->where('short_name', $short)
                ->where('id', '!=', $target['id'])
                ->first();
            if ($blocker !== null) {
                DB::table('countries')->where('id', $target['id'])->update([
                    'short_name' => $target['short_name'],
                ]);
                $skipped[] = [
                    'id' => $target['id'],
                    'reason' => 'target short_name is already used by another countries row',
                    'blocking_countries_id' => (int) $blocker->id,
                ];

                continue;
            }

            DB::table('countries')->where('id', $target['id'])->update([
                'short_name' => $short,
                'iso_code' => $iso,
                'tel_prefix' => $tel,
                'updated_at' => now(),
            ]);
            $corrected++;
        }

        return [
            'corrected' => $corrected,
            'skipped' => $skipped,
        ];
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
