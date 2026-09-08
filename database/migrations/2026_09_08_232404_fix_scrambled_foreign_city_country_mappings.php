<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CitiesTableSeeder mapped East-African capitals to the wrong countries
 * (e.g. Johannesburg under DRC). Re-home them and hide Johannesburg —
 * South Africa is not in the countries list.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $fixes = [
            'Bujumbura' => 'Burundi',
            'Juba' => 'South Sudan',
            'Kinshasa' => 'DRC',
            'Kigali' => 'Rwanda',
        ];

        foreach ($fixes as $cityName => $countryName) {
            $countryId = DB::table('countries')->where('name', $countryName)->value('id');
            if ($countryId === null) {
                continue;
            }

            DB::table('cities')
                ->where('name', $cityName)
                ->update([
                    'country_id' => $countryId,
                    'status' => 1,
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]);
        }

        $rwandaId = DB::table('countries')->where('name', 'Rwanda')->value('id');
        if ($rwandaId !== null) {
            $kigaliAttributes = [
                'status' => 1,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('cities', 'state_id')) {
                $kigaliAttributes['state_id'] = DB::table('cities')
                    ->whereNotNull('state_id')
                    ->value('state_id') ?? 1;
            }

            DB::table('cities')->updateOrInsert(
                ['name' => 'Kigali', 'country_id' => $rwandaId],
                $kigaliAttributes,
            );
        }

        // Not a DRC district — hide until/unless South Africa is added as a country.
        DB::table('cities')
            ->where('name', 'Johannesburg')
            ->update([
                'status' => 0,
                'deleted_at' => $now,
                'updated_at' => $now,
            ]);

        Cache::forget('cities');
        Cache::forget('countries');
    }

    public function down(): void
    {
        // Irreversible data correction — intentional.
    }
};
