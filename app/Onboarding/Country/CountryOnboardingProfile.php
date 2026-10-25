<?php

namespace App\Onboarding\Country;

/**
 * One country's onboarding defaults.
 *
 * A new country is a new class. The steps read this profile, so adding Kenya,
 * India or Brazil does not change the step classes.
 */
interface CountryOnboardingProfile
{
    public function key(): string;

    public function matches(?string $country): bool;

    /**
     * Uganda requires an EMIS / ministry code. Other countries do not, until
     * their own profile says they do.
     */
    public function requiresMinistryCode(): bool;

    public function ministryCodeLabel(): string;

    /**
     * Currency code for this country, or null when the profile does not set one.
     */
    public function currency(): ?string;

    /**
     * Curriculum option the step offers first. Uganda stays on uneb.
     */
    public function defaultCurriculum(): string;

    /**
     * Term names and dates for the academic year's calendar year.
     *
     * @return list<array{name: string, start: string, end: string}>
     */
    public function defaultTerms(int $year): array;

    /**
     * Recommended classes, streams and subjects for a school type.
     * Null keeps today's defaults for that type.
     */
    public function preset(?string $category): ?SchoolTypePreset;
}
