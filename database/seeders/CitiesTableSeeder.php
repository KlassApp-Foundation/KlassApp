<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Reference\Geography;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Depends on CountriesTableSeeder and StatesTableSeeder.
 * Every city is stored with the state that belongs to its country.
 * Uganda districts use the region state. Other cities use that
 * country's single state.
 */
class CitiesTableSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasColumn('cities', 'state_id')) {
            throw new RuntimeException('cities.state_id is missing. Run migrations before CitiesTableSeeder.');
        }

        $ugandaId = DB::table('countries')->where('name', 'Uganda')->value('id');
        if ($ugandaId === null) {
            throw new RuntimeException('CitiesTableSeeder requires Uganda. Seed countries first.');
        }

        foreach (Geography::ugandaDistricts() as $region => $districts) {
            $stateId = $this->stateId((int) $ugandaId, $region);
            foreach ($districts as $district) {
                $this->city((int) $ugandaId, $stateId, $district);
            }
        }

        foreach (Geography::foreignCities() as $city) {
            $countryId = DB::table('countries')->where('name', $city['country'])->value('id');
            if ($countryId === null) {
                throw new RuntimeException("CitiesTableSeeder requires country {$city['country']}.");
            }
            $stateName = Geography::otherStateNames()[$city['country']];
            $this->city((int) $countryId, $this->stateId((int) $countryId, $stateName), $city['name']);
        }
    }

    private function stateId(int $countryId, string $name): int
    {
        $id = DB::table('states')
            ->where('country_id', $countryId)
            ->where('name', $name)
            ->value('id');

        if ($id === null) {
            throw new RuntimeException("CitiesTableSeeder requires state {$name} for country id {$countryId}. Seed states first.");
        }

        return (int) $id;
    }

    private function city(int $countryId, int $stateId, string $name): void
    {
        ReferenceUpsert::sync('cities', [
            'country_id' => $countryId,
            'name' => $name,
        ], [
            'state_id' => $stateId,
            'status' => 1,
            'deleted_at' => null,
        ]);
    }
}
