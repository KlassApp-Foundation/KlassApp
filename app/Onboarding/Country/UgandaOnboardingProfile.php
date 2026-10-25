<?php

namespace App\Onboarding\Country;

/**
 * Uganda, as onboarding already behaves: EMIS is required, the curriculum
 * starts on UNEB, and the wizard's three term dates are the calendar.
 */
// FLAGGED(product-decision): EMIS, UGX, uneb, term dates, and class presets are PHP constants. see docs/onboarding-engine-plan.md
final class UgandaOnboardingProfile implements CountryOnboardingProfile
{
    public function key(): string
    {
        return 'uganda';
    }

    public function matches(?string $country): bool
    {
        if ($country === null) {
            return false;
        }

        $country = trim($country);
        if ($country === '') {
            return false;
        }

        return strcasecmp($country, 'Uganda') === 0
            || strcasecmp($country, 'UG') === 0;
    }

    public function requiresMinistryCode(): bool
    {
        return true;
    }

    public function ministryCodeLabel(): string
    {
        return 'EMIS / Ministry code';
    }

    public function currency(): ?string
    {
        return 'UGX';
    }

    public function defaultCurriculum(): string
    {
        return 'uneb';
    }

    public function defaultTerms(int $year): array
    {
        return [
            ['name' => 'Term 1', 'start' => $year.'-02-03', 'end' => $year.'-05-02'],
            ['name' => 'Term 2', 'start' => $year.'-05-26', 'end' => $year.'-08-29'],
            ['name' => 'Term 3', 'start' => $year.'-09-22', 'end' => $year.'-12-19'],
        ];
    }

    public function preset(?string $category): ?SchoolTypePreset
    {
        return SchoolTypePreset::uganda($category);
    }
}
