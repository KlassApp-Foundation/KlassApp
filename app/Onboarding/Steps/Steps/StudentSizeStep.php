<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Services\OnboardingStepsService;

class StudentSizeStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'student_size';
    }

    public function question(): string
    {
        return 'About how many students does your school have?';
    }

    public function inputType(): string
    {
        return 'choice';
    }

    public function options(School $school): array
    {
        return array_map(
            fn (string $v) => ['value' => $v, 'label' => $v],
            OnboardingStepsService::STUDENT_SIZE_OPTIONS
        );
    }

    public function normalize(mixed $raw): mixed
    {
        $v = is_string($raw) ? trim($raw) : $raw;
        if (! is_string($v)) {
            return $v;
        }
        foreach (OnboardingStepsService::STUDENT_SIZE_OPTIONS as $opt) {
            if (strcasecmp($v, $opt) === 0) {
                return $opt;
            }
        }

        return $v;
    }

    public function validate(School $school, mixed $normalized): void
    {
        if (! is_string($normalized) || ! in_array($normalized, OnboardingStepsService::STUDENT_SIZE_OPTIONS, true)) {
            $this->reject('Choose one of the school size options.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engine->saveStudentSize($school, (string) $normalized);
    }
}
