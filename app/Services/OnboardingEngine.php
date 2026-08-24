<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnboardingEngine
{
    /**
     * Persist the real school name, rejecting placeholders and duplicate names.
     *
     * @throws ValidationException
     */
    public function saveSchoolName(School $school, string $name): School
    {
        $name = trim($name);

        $validator = Validator::make(
            ['name' => $name],
            ['name' => 'required|string|min:3'],
            [
                'name.required' => 'Enter your real school name.',
                'name.min' => 'The school name must be at least 3 characters.',
            ]
        );
        $validator->validate();

        if (OnboardingStepsService::isPlaceholderSchoolName($name)) {
            throw ValidationException::withMessages(['name' => 'Enter your real school name.']);
        }

        // If the name is changing and the new name collides with another school,
        // resolve a unique suffix the same way new signups do.
        if ($school->name !== $name
            && School::where('name', $name)->where('id', '!=', $school->id)->exists()
        ) {
            $name = app(SchoolSignupBootstrapService::class)->uniqueSchoolName($name);
        }

        $school->name = $name;
        $school->slug = Str::slug($name);
        $school->save();

        return $school;
    }

    /**
     * Persist the school's registration country and linked country_id if known.
     *
     * @throws ValidationException
     */
    public function saveCountry(School $school, string $country): void
    {
        $country = trim($country);

        if ($country === '') {
            throw ValidationException::withMessages(['country' => 'Choose a country.']);
        }

        OnboardingStepsService::persistCountry($school, $country);
    }

    /**
     * Persist the selected curriculum.
     *
     * @throws ValidationException
     */
    public function saveCurriculum(School $school, string $curriculum): void
    {
        $curriculum = strtolower(trim($curriculum));

        if ($curriculum === '') {
            throw ValidationException::withMessages(['curriculum' => 'Choose a curriculum.']);
        }

        if (! in_array($curriculum, ['uneb', 'cambridge', 'montessori', 'other'], true)) {
            throw ValidationException::withMessages(['curriculum' => 'Choose a valid curriculum.']);
        }

        $school->curriculum = $curriculum;
        $school->save();
    }

    /**
     * Persist the selected school category and seed canonical defaults.
     *
     * @throws ValidationException
     */
    public function saveSchoolCategory(School $school, string $category): void
    {
        $category = trim($category);

        if ($category === '' || ! array_key_exists($category, SchoolCategorySeeder::CATEGORIES)) {
            throw ValidationException::withMessages(['schoolCategory' => 'Choose a school category.']);
        }

        $school->school_category = $category;
        $school->save();

        SchoolCategorySeeder::seed($school);
    }
}
