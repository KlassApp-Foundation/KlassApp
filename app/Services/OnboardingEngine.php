<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
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

    /**
     * Persist the EMIS / Ministry code for Ugandan schools.
     *
     * @throws ValidationException
     */
    public function saveEmis(School $school, string $ministryCode): void
    {
        $ministryCode = trim($ministryCode);

        if (! OnboardingStepsService::isUganda($school->registration_country)) {
            return;
        }

        if ($ministryCode === '') {
            throw ValidationException::withMessages(['ministryCode' => 'Enter your EMIS / ministry code.']);
        }

        $school->ministry_code = $ministryCode;
        $school->save();
    }

    /**
     * Persist the UNEB centre number if the column exists.
     *
     * null means "not asked yet" and is a no-op.
     * '' means "asked and skipped" and is stored as the empty string.
     */
    public function saveUnebCenter(School $school, ?string $unebCenterNumber): void
    {
        if (! Schema::hasColumn('schools', 'uneb_center_number')) {
            return;
        }

        if ($unebCenterNumber === null) {
            return;
        }

        $school->uneb_center_number = trim($unebCenterNumber);
        $school->save();
    }

    /**
     * Persist or update the school's academic year.
     *
     * @throws ValidationException
     */
    public function saveAcademicYear(School $school, string $name, ?string $start = null, ?string $end = null, ?string $description = null): AcademicYear
    {
        $name = trim($name);

        if ($name === '') {
            throw ValidationException::withMessages(['academicYear' => 'Enter an academic year.']);
        }

        $hasStart = $start !== null && trim($start) !== '';
        $hasEnd = $end !== null && trim($end) !== '';

        if ($hasStart !== $hasEnd) {
            throw ValidationException::withMessages(['academicYear' => 'Provide both start and end dates.']);
        }

        if ($hasStart) {
            $validator = Validator::make(
                ['academicYearStart' => $start, 'academicYearEnd' => $end],
                [
                    'academicYearStart' => 'required|date',
                    'academicYearEnd' => 'required|date|after:academicYearStart',
                ],
                [
                    'academicYearEnd.after' => 'The academic year end date must be after the start date.',
                ]
            );
            $validator->validate();
        }

        $yearName = preg_match('/\d{4}/', $name, $matches) ? $matches[0] : (string) now()->year;

        $startDate = $hasStart
            ? Carbon::parse($start)->startOfDay()
            : now()->setYear((int) $yearName)->startOfYear();

        $endDate = $hasStart
            ? Carbon::parse($end)->endOfDay()
            : now()->setYear((int) $yearName)->endOfYear();

        $year = AcademicYear::firstOrCreate(
            ['school_id' => $school->id],
            [
                'name' => $name,
                'description' => $description ?? 'Current Academic Year',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 1,
            ]
        );

        if (! $year->wasRecentlyCreated) {
            $year->name = $name;

            if ($description !== null && trim($description) !== '') {
                $year->description = trim($description);
            }

            if ($hasStart) {
                $year->start_date = $startDate;
                $year->end_date = $endDate;
            }

            $year->save();
        }

        Cache::forget('academic_year_for_school_'.$school->id);

        if ($school->school_category) {
            SchoolCategorySeeder::seed($school);
        }

        return $year;
    }
}
