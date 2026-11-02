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

        // Soft-launch 1g: AI is opt-in. Site-admin create must not default assistant on.
        // If the form explicitly enables Toshi, keep mode coherent (Assistant);
        // otherwise use TOSHI_DEFAULT_MODE (preview unless set to onboarding).
        $explicitEnabled = array_key_exists('toshi_enabled', $validated)
            && $validated['toshi_enabled'] !== null
            && (bool) $validated['toshi_enabled'];

        if (! array_key_exists('toshi_enabled', $validated) || $validated['toshi_enabled'] === null) {
            $validated['toshi_enabled'] = 0;
        }

        if (! array_key_exists('toshi_mode', $validated) || $validated['toshi_mode'] === null) {
            $validated['toshi_mode'] = $explicitEnabled
                ? \App\Enums\ToshiMode::Assistant
                : \App\Enums\ToshiMode::configuredDefault();
        }

        return $this->keepCountryFieldsTogether(School::create($validated));
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

        return $this->keepCountryFieldsTogether($school);
    }

    /**
     * country_id and registration_country describe the same choice.
     * A saved country id sets the name. A saved name with no id sets the id.
     */
    private function keepCountryFieldsTogether(School $school): School
    {
        $school->refresh();

        if ($school->country_id) {
            $name = Country::query()->find($school->country_id)?->name;
            if (is_string($name) && $name !== '') {
                OnboardingStepsService::persistCountry($school, $name);

                return $school->fresh();
            }
        }

        if (filled($school->registration_country)) {
            OnboardingStepsService::persistCountry($school, (string) $school->registration_country);
        }

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
        $data = $this->alignCountryFields($data);

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
            'toshi_mode' => 'nullable|in:onboarding,preview,assistant',
        ];

        if (Schema::hasColumn('schools', 'uneb_center_number')) {
            $rules['uneb_center_number'] = 'nullable|string|max:50';
        }

        return Validator::make($data, $rules, [
            'ministry_code.required' => 'EMIS / Ministry code is required for Uganda schools.',
        ])->validate();
    }

    /**
     * country_id and registration_country are one choice. When both are sent
     * and they name different countries, the save is rejected. Otherwise the
     * saved name is the countries row's name, not a short code.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function alignCountryFields(array $data): array
    {
        $id = isset($data['country_id']) && $data['country_id'] !== '' && $data['country_id'] !== null
            ? (int) $data['country_id']
            : null;
        $raw = trim((string) ($data['registration_country'] ?? ''));

        $byId = $id !== null ? $this->activeCountryById($id) : null;
        $byText = $raw !== '' ? OnboardingStepsService::activeCountriesMatching($raw) : collect();

        if ($byText->count() > 1) {
            throw ValidationException::withMessages([
                'registration_country' => 'Choose one country. More than one country row matches that name.',
            ]);
        }

        $named = $byText->first();
        if ($byId !== null && $named !== null && (int) $byId->id !== (int) $named->id) {
            throw ValidationException::withMessages([
                'country_id' => 'The country and the country name do not match.',
                'registration_country' => 'The country and the country name do not match.',
            ]);
        }

        $country = $byId ?? $named;
        if ($country !== null) {
            $data['country_id'] = $country->id;
            $data['registration_country'] = $country->name;
        }

        return $data;
    }

    private function activeCountryById(int $id): ?Country
    {
        $query = Country::query()->whereKey($id);
        if (Schema::hasColumn('countries', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->first();
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
