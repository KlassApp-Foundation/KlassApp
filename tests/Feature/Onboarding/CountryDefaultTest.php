<?php

namespace Tests\Feature\Onboarding;

use App\Services\OnboardingStepsService;
use Tests\TestCase;

/**
 * The country a blank onboarding answer means is decided in ONE place:
 * OnboardingStepsService::countryOrDefault(). Toshi v1 (AgentToshi) and the v2
 * wizard (ManualOnboardingWizard) call it and store what it returns; neither
 * keeps its own fallback, so the two cannot drift.
 */
class CountryDefaultTest extends TestCase
{
    public function test_a_blank_answer_means_the_service_default(): void
    {
        $this->assertSame('Uganda', OnboardingStepsService::countryOrDefault(null));
        $this->assertSame('Uganda', OnboardingStepsService::countryOrDefault(''));
        $this->assertSame('Uganda', OnboardingStepsService::countryOrDefault('   '));
    }

    public function test_an_answer_is_kept_and_trimmed(): void
    {
        $this->assertSame('Kenya', OnboardingStepsService::countryOrDefault('Kenya'));
        $this->assertSame('Kenya', OnboardingStepsService::countryOrDefault('  Kenya  '));
    }

    public function test_toshi_v1_calls_the_service_and_does_not_default_the_country_itself(): void
    {
        $source = file_get_contents(base_path('app/Livewire/AgentToshi.php'));

        $this->assertStringContainsString(
            'OnboardingStepsService::countryOrDefault($this->schoolCountry)',
            $source,
            'Toshi v1 stores the country the onboarding service resolves',
        );
        $this->assertStringNotContainsString(
            "schoolCountry ?: 'Uganda'",
            $source,
            'Toshi v1 keeps no country fallback of its own',
        );
    }

    public function test_the_v2_wizard_calls_the_service_and_does_not_default_the_country_itself(): void
    {
        $source = file_get_contents(base_path('app/Livewire/ManualOnboardingWizard.php'));

        $this->assertStringContainsString(
            'OnboardingStepsService::countryOrDefault($school->registration_country)',
            $source,
            'the v2 wizard shows the country the onboarding service resolves',
        );
        $this->assertStringNotContainsString(
            "registration_country ?: 'Uganda'",
            $source,
            'the v2 wizard keeps no country fallback of its own',
        );
    }
}
