<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\Country;
use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Services\OnboardingStepsService;

class CountryStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'country';
    }

    public function question(): string
    {
        $country = OnboardingStepsService::DEFAULT_COUNTRY;

        return "Which country is your school in? {$country} is selected for now. Reply with {$country} to keep it, or another country.";
    }

    public function inputType(): string
    {
        return 'choice';
    }

    public function options(School $school): array
    {
        $names = Country::query()->orderBy('name')->pluck('name');
        if ($names->isEmpty()) {
            $names = collect([OnboardingStepsService::DEFAULT_COUNTRY, 'Kenya', 'Tanzania']);
        }

        $names = $names
            ->sortBy(fn ($name) => strcasecmp((string) $name, OnboardingStepsService::DEFAULT_COUNTRY) === 0
                ? '0'
                : '1'.strtolower((string) $name))
            ->values();

        return $names
            ->map(fn ($name) => ['value' => (string) $name, 'label' => (string) $name])
            ->all();
    }

    public function normalize(mixed $raw): mixed
    {
        $value = is_string($raw) ? trim($raw) : $raw;
        if ($value === null || $value === '') {
            return OnboardingStepsService::DEFAULT_COUNTRY;
        }

        return $value;
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engineCall(fn () => $this->engine->saveCountry($school, (string) $normalized));
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
