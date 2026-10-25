<?php

namespace App\Onboarding\Country;

/**
 * Fallback for a country that does not have its own profile yet.
 *
 * Kenya, India and Brazil land here until their profiles are registered.
 * Ministry code stays off. Term dates stay the calendar the wizard already
 * shows every school, so a school with no profile does not change prefill.
 */
final class GenericOnboardingProfile implements CountryOnboardingProfile
{
    public function key(): string
    {
        return 'generic';
    }

    public function matches(?string $country): bool
    {
        return false;
    }

    public function requiresMinistryCode(): bool
    {
        return false;
    }

    public function ministryCodeLabel(): string
    {
        return 'EMIS / Ministry code';
    }

    public function currency(): ?string
    {
        return null;
    }

    public function defaultCurriculum(): string
    {
        return 'uneb';
    }

    public function defaultTerms(int $year): array
    {
        return (new UgandaOnboardingProfile())->defaultTerms($year);
    }
}
