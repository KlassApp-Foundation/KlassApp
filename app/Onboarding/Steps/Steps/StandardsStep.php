<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\AcademicYear;
use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Services\OnboardingStepsService;

class StandardsStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'standards';
    }

    public function question(): string
    {
        return 'Confirm your classes and streams.';
    }

    public function inputType(): string
    {
        return 'structure';
    }

    public function normalize(mixed $raw): mixed
    {
        if ($raw === 'skip' && in_array('standards', OnboardingStepsService::OPTIONAL_STEPS, true)) {
            return 'skip';
        }

        return is_array($raw) ? $raw : [];
    }

    public function validate(School $school, mixed $normalized): void
    {
        if ($normalized === 'skip') {
            if (! in_array('standards', OnboardingStepsService::OPTIONAL_STEPS, true)) {
                $this->reject('This step cannot be skipped.');
            }

            return;
        }
        if (! is_array($normalized)) {
            $this->reject('Provide a valid list for this step.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        if ($normalized === 'skip') {
            OnboardingStepsService::markStepSkipped($school, 'standards');

            return;
        }
        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            $this->reject('Create an academic year before this step.');
        }
        $this->engine->saveStandards($school, $year, $normalized);
    }
}
