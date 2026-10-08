<?php

namespace Tests\Feature\Onboarding;

use App\Models\School;
use App\Services\OnboardingStepsService;
use Tests\TestCase;

/**
 * K25 (PR1): dashboard surfaces (setup bar, sidebar chip) use neutral step
 * names from the one OnboardingStepsService source. Curriculum-specific names
 * appear only when the curriculum defines them — today none do, so "EMIS /
 * Ministry code" and "UNEB centre number" never render on the dashboard.
 */
class StepLabelsNeutralTest extends TestCase
{
    public function test_dashboard_surfaces_use_neutral_step_names(): void
    {
        $school = new School(['registration_country' => 'Uganda', 'curriculum' => 'UNEB']);

        $this->assertSame(
            'Registration code',
            OnboardingStepsService::displayLabel('emis', OnboardingStepsService::ALL_STEPS['emis'], $school)
        );
        $this->assertSame(
            'Exam centre number',
            OnboardingStepsService::displayLabel('uneb_center', OnboardingStepsService::ALL_STEPS['uneb_center'], $school)
        );
    }

    public function test_curriculum_specific_names_only_when_the_curriculum_defines_them(): void
    {
        // No curriculum defines names today — the seam returns nothing and the
        // neutral set wins even for UNEB schools.
        $this->assertSame([], OnboardingStepsService::curriculumStepLabels('UNEB'));
        $this->assertSame([], OnboardingStepsService::curriculumStepLabels(null));

        $school = new School(['curriculum' => 'UNEB']);
        $this->assertSame(
            'Exam centre number',
            OnboardingStepsService::displayLabel('uneb_center', OnboardingStepsService::ALL_STEPS['uneb_center'], $school)
        );
    }

    public function test_other_steps_keep_their_labels_and_wizard_wording_is_untouched(): void
    {
        $school = new School(['curriculum' => 'UNEB']);

        $this->assertSame(
            'Students',
            OnboardingStepsService::displayLabel('students', OnboardingStepsService::ALL_STEPS['students'], $school)
        );

        // Wizard context (any surface outside the dashboard chips) is unchanged.
        $this->assertSame('EMIS / Ministry code', OnboardingStepsService::labelForContext('emis', 'EMIS / Ministry code', 'wizard'));
        $this->assertSame('UNEB centre number', OnboardingStepsService::labelForContext('uneb_center', 'UNEB centre number'));
    }
}
