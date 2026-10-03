<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;

class EmisStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'emis';
    }

    public function question(): string
    {
        return 'What is your EMIS / ministry code?';
    }

    public function inputType(): string
    {
        return 'text';
    }

    public function validate(School $school, mixed $normalized): void
    {
        if (! is_string($normalized) || trim($normalized) === '') {
            $this->reject('Enter your EMIS / ministry code.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $this->validate($school, $normalized);
        $this->engine->saveEmis($school, (string) $normalized);
    }
}
