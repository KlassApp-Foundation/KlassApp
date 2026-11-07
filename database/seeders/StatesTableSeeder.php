<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Reference\CountryCodes;
use App\Reference\Geography;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Depends on CountriesTableSeeder. Every state is inserted against
 * a country row that already exists. Uganda gets its four regions.
 * Every other country gets one state.
 */
class StatesTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_keys(CountryCodes::corrected()) as $countryName) {
            $countryId = DB::table('countries')->where('name', $countryName)->value('id');
            if ($countryId === null) {
                throw new RuntimeException("StatesTableSeeder requires country {$countryName}. Seed countries first.");
            }

            $names = $countryName === 'Uganda'
                ? Geography::ugandaRegions()
                : [Geography::otherStateNames()[$countryName]];

            foreach ($names as $name) {
                ReferenceUpsert::sync('states', [
                    'country_id' => $countryId,
                    'name' => $name,
                ], [
                    'status' => 1,
                    'deleted_at' => null,
                ]);
            }
        }
    }
}
