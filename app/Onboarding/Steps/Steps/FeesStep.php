<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;

class FeesStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'fees';
    }

    public function question(): string
    {
        return 'Add fee structures.';
    }

    public function inputType(): string
    {
        return 'list';
    }

    public function normalize(mixed $raw): mixed
    {
        return is_array($raw) ? $raw : [];
    }

    public function validate(School $school, mixed $normalized): void
    {
        if (! is_array($normalized) || $normalized === []) {
            $this->reject('Add at least one fee structure.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engine->saveFees($school, $normalized);
    }
}
