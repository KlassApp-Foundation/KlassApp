<?php

namespace App\Onboarding\Steps;

use App\Models\School;
use App\Onboarding\Steps\Contracts\OnboardingStep;
use App\Onboarding\Steps\Steps\AcademicYearStep;
use App\Onboarding\Steps\Steps\CountryStep;
use App\Onboarding\Steps\Steps\CurriculumStep;
use App\Onboarding\Steps\Steps\EmisStep;
use App\Onboarding\Steps\Steps\FeesStep;
use App\Onboarding\Steps\Steps\PlanSelectionStep;
use App\Onboarding\Steps\Steps\SchoolCategoryStep;
use App\Onboarding\Steps\Steps\SchoolNameStep;
use App\Onboarding\Steps\Steps\StandardsStep;
use App\Onboarding\Steps\Steps\StudentSizeStep;
use App\Onboarding\Steps\Steps\StudentsStep;
use App\Onboarding\Steps\Steps\SubjectsStep;
use App\Onboarding\Steps\Steps\TeachersStep;
use App\Onboarding\Steps\Steps\TermsStep;
use App\Onboarding\Steps\Steps\UnebCenterStep;
use App\Onboarding\Steps\Steps\WhatsAppVerifyStep;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;

class StepRegistry
{
    /**
     * @var list<class-string<OnboardingStep>>
     */
    private const STEP_CLASSES = [
        SchoolNameStep::class,
        StudentSizeStep::class,
        CountryStep::class,
        CurriculumStep::class,
        SchoolCategoryStep::class,
        EmisStep::class,
        UnebCenterStep::class,
        AcademicYearStep::class,
        StandardsStep::class,
        SubjectsStep::class,
        TeachersStep::class,
        StudentsStep::class,
        TermsStep::class,
        FeesStep::class,
        WhatsAppVerifyStep::class,
        PlanSelectionStep::class,
    ];

    public function __construct(private OnboardingEngine $engine)
    {
    }

    /**
     * @return list<OnboardingStep>
     */
    public function all(): array
    {
        return array_map(
            fn (string $class) => app($class, ['engine' => $this->engine]),
            self::STEP_CLASSES
        );
    }

    /**
     * Ordered applicable steps for this school (same filter as OnboardingStepsService).
     *
     * @return list<OnboardingStep>
     */
    public function forSchool(School $school): array
    {
        $applicable = OnboardingStepsService::applicableSteps($school);

        return array_values(array_filter(
            $this->all(),
            fn (OnboardingStep $step) => array_key_exists($step->key(), $applicable) && $step->applies($school)
        ));
    }

    public function nextUnfinished(School $school, ?int $userId = null): ?OnboardingStep
    {
        foreach ($this->forSchool($school) as $step) {
            if (! $step->isComplete($school, $userId)) {
                return $step;
            }
        }

        return null;
    }

    public function byKey(string $key): ?OnboardingStep
    {
        foreach ($this->all() as $step) {
            if ($step->key() === $key) {
                return $step;
            }
        }

        return null;
    }
}
