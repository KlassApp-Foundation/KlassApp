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
     * Step class per canonical key. The *order* and the *membership* come from
     * OnboardingStepsService::ALL_STEPS; this map only says which class answers
     * for a key, because two class names cannot be derived from their keys
     * (uneb_center → UnebCenterStep, whatsapp_verify → WhatsAppVerifyStep).
     *
     * @var array<string, class-string<OnboardingStep>>
     */
    private const CLASSES = [
        'school_name' => SchoolNameStep::class,
        'student_size' => StudentSizeStep::class,
        'country' => CountryStep::class,
        'curriculum' => CurriculumStep::class,
        'school_category' => SchoolCategoryStep::class,
        'emis' => EmisStep::class,
        'uneb_center' => UnebCenterStep::class,
        'academic_year' => AcademicYearStep::class,
        'standards' => StandardsStep::class,
        'subjects' => SubjectsStep::class,
        'teachers' => TeachersStep::class,
        'students' => StudentsStep::class,
        'terms' => TermsStep::class,
        'fees' => FeesStep::class,
        'whatsapp_verify' => WhatsAppVerifyStep::class,
        'plan_selection' => PlanSelectionStep::class,
    ];

    public function __construct(private OnboardingEngine $engine)
    {
    }

    /**
     * @return list<OnboardingStep>
     */
    public function all(): array
    {
        $steps = [];

        foreach (array_keys(OnboardingStepsService::ALL_STEPS) as $key) {
            $class = self::CLASSES[$key] ?? null;
            if ($class === null) {
                throw new \RuntimeException("Onboarding step [{$key}] has no class in StepRegistry.");
            }

            $steps[] = app($class, ['engine' => $this->engine]);
        }

        return $steps;
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
