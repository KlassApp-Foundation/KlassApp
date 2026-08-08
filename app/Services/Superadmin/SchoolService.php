<?php

namespace App\Services\Superadmin;

use App\Models\Country;
use App\Models\School;
use App\Services\OnboardingStepsService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

/**
 * Shared school mutators for Livewire CreateSchool and Toshi platform tools.
 * Mirrors submitSchool() create/update behaviour from Batch A audit.
 */
class SchoolService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): School
    {
        $validated = $this->validate($data, null);

        return School::create($validated);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): School
    {
        $school = School::findOrFail($id);
        $validated = $this->validate($data, $id);
        $school->update($validated);

        return $school->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validate(array $data, ?int $schoolId): array
    {
        $emailRule = ['required', 'email'];
        if ($schoolId === null) {
            $emailRule[] = Rule::unique('schools', 'email');
        } else {
            $emailRule[] = Rule::unique('schools', 'email')->ignore($schoolId);
        }

        // Shared with #185 School Details (DetailRequest): EMIS/ministry code is
        // required for Uganda schools, optional otherwise. UNEB centre number is
        // always optional (only relevant when curriculum is UNEB).
        $ministryRule = ['nullable', 'exists:emis_schools,emis_code'];
        if ($this->countryIsUganda($data)) {
            $ministryRule = ['required', 'exists:emis_schools,emis_code'];
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'phone' => 'required|numeric|digits:10',
            'address' => 'required|string|max:500',
            'city_id' => 'required|exists:cities,id',
            'country_id' => 'required|exists:countries,id',
            'pincode' => 'required|numeric',
            'registration_country' => 'nullable|string|max:255',
            'student_size' => 'nullable|string|max:255',
            'ministry_code' => $ministryRule,
            'curriculum' => 'nullable|string|max:255',
            'status' => 'nullable',
            'toshi_enabled' => 'nullable|boolean',
        ];

        if (Schema::hasColumn('schools', 'uneb_center_number')) {
            $rules['uneb_center_number'] = 'nullable|string|max:50';
        }

        return Validator::make($data, $rules, [
            'ministry_code.required' => 'EMIS / Ministry code is required for Uganda schools.',
        ])->validate();
    }

    /**
     * Uganda detection mirrors #185: prefer the free-text registration_country,
     * fall back to the selected countries row name.
     *
     * @param  array<string, mixed>  $data
     */
    private function countryIsUganda(array $data): bool
    {
        if (OnboardingStepsService::isUganda($data['registration_country'] ?? null)) {
            return true;
        }

        if (! empty($data['country_id'])) {
            $country = Country::query()->find($data['country_id']);

            return $country !== null && OnboardingStepsService::isUganda($country->name);
        }

        return false;
    }
}
