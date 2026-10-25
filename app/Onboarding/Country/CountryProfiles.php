<?php

namespace App\Onboarding\Country;

/**
 * Resolves the onboarding profile for a school's country.
 *
 * Uganda is built in. Register another profile (Kenya, then India and Brazil)
 * and the steps pick it up. Nothing else has to change.
 */
final class CountryProfiles
{
    /** @var list<CountryOnboardingProfile> */
    private static array $plugged = [];

    public static function register(CountryOnboardingProfile $profile): void
    {
        self::$plugged[] = $profile;
    }

    public static function reset(): void
    {
        self::$plugged = [];
    }

    public static function forCountry(?string $country): CountryOnboardingProfile
    {
        foreach (self::candidates() as $profile) {
            if ($profile->matches($country)) {
                return $profile;
            }
        }

        return new GenericOnboardingProfile();
    }

    public static function requiresMinistryCode(?string $country): bool
    {
        if ($country === null || trim($country) === '') {
            return false;
        }

        return self::forCountry($country)->requiresMinistryCode();
    }

    /**
     * Built-in profiles are checked first, so a plug-in cannot take Uganda's place.
     *
     * @return list<CountryOnboardingProfile>
     */
    private static function candidates(): array
    {
        return array_merge([new UgandaOnboardingProfile()], self::$plugged);
    }
}
