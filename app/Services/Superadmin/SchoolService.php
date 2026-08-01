<?php

namespace App\Services\Superadmin;

use App\Models\School;
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

        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'phone' => 'required|numeric|digits:10',
            'address' => 'required|string|max:500',
            'city_id' => 'required|exists:cities,id',
            'country_id' => 'required|exists:countries,id',
            'pincode' => 'required|numeric',
            'registration_country' => 'nullable|string|max:255',
            'student_size' => 'nullable|string|max:255',
            'ministry_code' => 'nullable|exists:emis_schools,emis_code',
            'curriculum' => 'nullable|string|max:255',
            'status' => 'nullable',
        ])->validate();
    }
}
