<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
 * down() puts the known seed codes back. A name that was not corrected,
 * or that matches more than one live row, is left as it is.
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
     * Puts the known seed codes back, in one transaction. A row is restored
     * only when it still holds the corrected code, so a skipped row is left
     * alone. A failure rolls the whole rollback back.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            $this->restore();
        });
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
            $rows = $this->liveRowsNamed($name);
            if ($rows->count() === 0) {
                $skipped[] = [
                    'id' => null,
                    'reason' => 'no countries row matched this name',
                    'name' => $name,
                ];

                continue;
            }
            if ($rows->count() > 1) {
                $skipped[] = [
                    'id' => null,
                    'reason' => 'more than one countries row matched this name',
                    'name' => $name,
                ];

                continue;
            }

            $row = $rows->first();
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
     * Live rows only. A soft-deleted namesake is not the row a school points at.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function liveRowsNamed(string $name): \Illuminate\Support\Collection
    {
        $query = DB::table('countries')->whereRaw('LOWER(name) = ?', [strtolower($name)]);
        if (Schema::hasColumn('countries', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('id')->get();
    }

    private function restore(): void
    {
        $corrected = self::codes();
        $previous = self::previousCodes();
        $targets = [];

        foreach ($previous as $name => $spec) {
            $rows = $this->liveRowsNamed($name);
            if ($rows->count() !== 1) {
                continue;
            }

            $row = $rows->first();
            $now = [(string) $row->short_name, (string) $row->iso_code, (string) $row->tel_prefix];
            if ($now !== $corrected[$name]) {
                continue;
            }

            $targets[] = [
                'id' => (int) $row->id,
                'short_name' => $row->short_name,
                'iso_code' => $row->iso_code,
                'tel_prefix' => $row->tel_prefix,
                'restore' => $spec,
            ];
        }

        foreach ($targets as $target) {
            DB::table('countries')->where('id', $target['id'])->update([
                'short_name' => 'T'.$target['id'],
            ]);
        }

        foreach ($targets as $target) {
            [$short, $iso, $tel] = $target['restore'];
            $blocker = DB::table('countries')
                ->where('short_name', $short)
                ->where('id', '!=', $target['id'])
                ->first();
            if ($blocker !== null) {
                DB::table('countries')->where('id', $target['id'])->update([
                    'short_name' => $target['short_name'],
                    'iso_code' => $target['iso_code'],
                    'tel_prefix' => $target['tel_prefix'],
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

    /**
     * The seed values this migration replaces. down() writes these back.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function previousCodes(): array
    {
        return [
            'Uganda' => ['AF', 'AFG', '256'],
            'Kenya' => ['AL', 'ALB', '355'],
            'Tanzania' => ['AR', 'ARG', '54'],
            'Rwanda' => ['AU', 'AUS', '61'],
            'Burundi' => ['CH', 'CHN', '86'],
            'South Sudan' => ['IN', 'IND', '91'],
            'DRC' => ['MA', 'MYS', '60'],
            'Nigeria' => ['SW', 'CHE', '41'],
            'Egypt' => ['EG', 'EGY', '20'],
            'Other' => ['OT', 'OT', '20'],
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
