<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;

class SchoolNameStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'school_name';
    }

    public function question(): string
    {
        return "What is your school's name?";
    }

    public function inputType(): string
    {
        return 'text';
    }

    public function validate(School $school, mixed $normalized): void
    {
        if (! is_string($normalized) || trim($normalized) === '') {
            $this->reject('Enter your real school name.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $this->validate($school, $normalized);
        $this->engine->saveSchoolName($school, (string) $normalized);
    }
}
