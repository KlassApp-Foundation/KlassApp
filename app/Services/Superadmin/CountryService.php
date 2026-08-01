<?php

namespace App\Services\Superadmin;

use App\Models\Country;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Shared country mutators for Livewire CountryForm and Toshi platform tools.
 * Mirrors submitCountry() create/update behaviour from Batch B audit.
 */
class CountryService
{
    /**
     * @param  array{name:string,short_name:string,iso_code:string,tel_prefix:string,status?:int|string}  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): Country
    {
        $validated = $this->validate($data);

        return Country::create($validated);
    }

    /**
     * @param  array{name:string,short_name:string,iso_code:string,tel_prefix:string,status?:int|string}  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): Country
    {
        $country = Country::findOrFail($id);
        $validated = $this->validate($data);
        $country->update($validated);

        return $country->fresh();
    }

    /**
     * @return array{name:string,short_name:string,iso_code:string,tel_prefix:string,status:int}
     *
     * @throws ValidationException
     */
    private function validate(array $data): array
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:255',
            'iso_code' => 'required|string|max:10',
            'tel_prefix' => 'required|string|max:20',
            'status' => 'required',
        ])->validate();
    }
}
