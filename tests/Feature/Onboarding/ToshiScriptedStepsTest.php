<?php

namespace Tests\Feature\Onboarding;

use App\Onboarding\Steps\StepRegistry;
use App\Services\OnboardingStepsService;
use Tests\TestCase;

/**
 * The scripted Toshi flow and the step registry must not own their own step
 * lists. Both are projections of OnboardingStepsService::ALL_STEPS; these
 * tests fail when a projection drifts.
 */
class ToshiScriptedStepsTest extends TestCase
{
    public function test_the_flow_is_the_canonical_list_without_the_steps_it_cannot_ask(): void
    {
        $scripted = OnboardingStepsService::toshiScriptedSteps();
        $canonical = array_keys(OnboardingStepsService::ALL_STEPS);

        $core = array_values(array_diff($scripted, OnboardingStepsService::TOSHI_PLATFORM_STEPS));
        $expected = array_map(
            fn (string $key): string => $key === 'school_name' ? 'school_info' : $key,
            array_values(array_diff($canonical, OnboardingStepsService::TOSHI_SCRIPTED_SKIPS)),
        );

        $this->assertSame($expected, $core);
    }

    public function test_platform_steps_sit_between_the_school_identity_and_the_academic_year(): void
    {
        $scripted = OnboardingStepsService::toshiScriptedSteps();

        $uneb = array_search('uneb_center', $scripted, true);
        $admin = array_search('admin_account', $scripted, true);
        $coAdmin = array_search('co_admin_invite', $scripted, true);
        $year = array_search('academic_year', $scripted, true);

        $this->assertIsInt($uneb);
        $this->assertIsInt($admin);
        $this->assertIsInt($coAdmin);
        $this->assertIsInt($year);
        $this->assertTrue($uneb < $admin && $admin < $coAdmin && $coAdmin < $year);
    }

    public function test_exams_and_school_pay_are_not_onboarding_steps(): void
    {
        $scripted = OnboardingStepsService::toshiScriptedSteps();

        $this->assertNotContains('exams', $scripted);
        $this->assertNotContains('school_pay', $scripted);
        $this->assertNotContains('exams', array_keys(OnboardingStepsService::ALL_STEPS));
        $this->assertNotContains('school_pay', array_keys(OnboardingStepsService::ALL_STEPS));
    }

    public function test_every_mandatory_step_is_in_the_scripted_flow(): void
    {
        $scripted = OnboardingStepsService::toshiScriptedSteps();

        foreach (['school_info', 'student_size', 'country', 'admin_account', 'academic_year', 'standards', 'subjects', 'terms', 'plan_selection'] as $key) {
            $this->assertContains($key, $scripted, "Mandatory step [{$key}] is missing from the scripted flow.");
        }
    }

    public function test_the_flow_starts_at_step_zero_and_ends_on_review(): void
    {
        $scripted = OnboardingStepsService::toshiScriptedSteps();

        $this->assertSame('school_info', $scripted[0]);
        $this->assertSame('review', $scripted[array_key_last($scripted)]);
    }

    public function test_no_step_appears_twice(): void
    {
        $scripted = OnboardingStepsService::toshiScriptedSteps();

        $this->assertSame($scripted, array_values(array_unique($scripted)));
    }

    public function test_the_registry_covers_every_canonical_step_in_order(): void
    {
        $registry = app(StepRegistry::class);

        $this->assertSame(
            array_keys(OnboardingStepsService::ALL_STEPS),
            array_map(fn ($step) => $step->key(), $registry->all()),
        );
    }
}
