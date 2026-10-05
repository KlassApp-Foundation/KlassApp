<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;

class CountryStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'country';
    }

    public function question(): string
    {
        return 'Which country is your school in?';
    }

    public function inputType(): string
    {
        return 'text';
    }

    public function validate(School $school, mixed $normalized): void
    {
        if (! is_string($normalized) || strlen(trim($normalized)) < 2) {
            $this->reject('Enter a country.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engine->saveCountry($school, (string) $normalized);
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        return $this->attributePreview($school, $normalized, 'registration_country', 'Country');
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        return $this->attributeSaveAndReport($school, $normalized, 'registration_country', 'Country');
    }
}
