<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;

class UnebCenterStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'uneb_center';
    }

    public function question(): string
    {
        return 'What is your UNEB centre number? (you can skip)';
    }

    public function inputType(): string
    {
        return 'text';
    }

    public function normalize(mixed $raw): mixed
    {
        if ($raw === null) {
            return '';
        }
        if (is_string($raw) && in_array(strtolower(trim($raw)), ['skip', 'none', 'n/a'], true)) {
            return '';
        }

        return is_string($raw) ? trim($raw) : $raw;
    }

    public function validate(School $school, mixed $normalized): void
    {
        if (! is_string($normalized)) {
            $this->reject('Enter a UNEB centre number or skip.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engine->saveUnebCenter($school, (string) $normalized);
    }
}
