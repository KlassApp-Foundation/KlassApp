<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;

class AcademicYearStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'academic_year';
    }

    public function question(): string
    {
        return 'What is the current academic year name?';
    }

    public function inputType(): string
    {
        return 'text';
    }

    public function normalize(mixed $raw): mixed
    {
        if (is_array($raw)) {
            return [
                'name' => trim((string) ($raw['name'] ?? '')),
                'start' => $raw['start'] ?? null,
                'end' => $raw['end'] ?? null,
            ];
        }
        if (is_string($raw) && trim($raw) !== '') {
            return ['name' => trim($raw), 'start' => null, 'end' => null];
        }

        return ['name' => (string) date('Y'), 'start' => null, 'end' => null];
    }

    public function validate(School $school, mixed $normalized): void
    {
        if (! is_array($normalized) || trim((string) ($normalized['name'] ?? '')) === '') {
            $this->reject('Enter an academic year name.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engine->saveAcademicYear(
            $school,
            (string) $normalized['name'],
            $normalized['start'] ?? null,
            $normalized['end'] ?? null
        );
    }
}
