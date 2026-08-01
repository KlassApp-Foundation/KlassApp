<?php

namespace App\Services\Superadmin;

use App\Models\City;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Shared city mutators for Livewire CityForm and Toshi platform tools.
 * Mirrors submitCity() create/update behaviour from Batch B audit.
 */
class CityService
{
    /**
     * @param  array{country_id:int|string,name:string,status?:int|string}  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): City
    {
        $validated = $this->validate($data);
        $validated = $this->withLegacyStateId($validated);

        return City::create($validated);
    }

    /**
     * @param  array{country_id:int|string,name:string,status?:int|string}  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): City
    {
        $city = City::findOrFail($id);
        $validated = $this->validate($data);
        $validated = $this->withLegacyStateId($validated);
        $city->update($validated);

        return $city->fresh();
    }

    /**
     * SQLite test DBs still have cities.state_id (drop skipped for sqlite).
     * Production MySQL dropped the column. Supply a harmless default when present.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function withLegacyStateId(array $validated): array
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('cities', 'state_id')
            && ! array_key_exists('state_id', $validated)) {
            $validated['state_id'] = 0;
        }

        return $validated;
    }

    /**
     * @return array{country_id:int,name:string,status:int}
     *
     * @throws ValidationException
     */
    private function validate(array $data): array
    {
        return Validator::make($data, [
            'country_id' => 'required|exists:countries,id',
            'name' => 'required|string|max:255',
            'status' => 'required',
        ])->validate();
    }
}
