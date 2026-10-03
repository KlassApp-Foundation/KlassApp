<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\AcademicYear;
use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Services\OnboardingStepsService;

class TeachersStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'teachers';
    }

    public function question(): string
    {
        return 'Add teachers now, or skip for later.';
    }

    public function inputType(): string
    {
        return 'skipable_list';
    }

    public function normalize(mixed $raw): mixed
    {
        if ($raw === 'skip' && in_array('teachers', OnboardingStepsService::OPTIONAL_STEPS, true)) {
            return 'skip';
        }

        return is_array($raw) ? $raw : [];
    }

    public function validate(School $school, mixed $normalized): void
    {
        if ($normalized === 'skip') {
            if (! in_array('teachers', OnboardingStepsService::OPTIONAL_STEPS, true)) {
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
            OnboardingStepsService::markStepSkipped($school, 'teachers');

            return;
        }
        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            $this->reject('Create an academic year before this step.');
        }
        $this->engine->saveTeachers($school, $year, $normalized);
    }
}
