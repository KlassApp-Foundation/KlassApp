<?php

namespace App\Onboarding\Steps;

use App\Models\School;
use App\Onboarding\Steps\Contracts\OnboardingStep;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
use InvalidArgumentException;

abstract class AbstractOnboardingStep implements OnboardingStep
{
    public function __construct(protected OnboardingEngine $engine)
    {
    }

    public function applies(School $school): bool
    {
        return array_key_exists($this->key(), OnboardingStepsService::applicableSteps($school));
    }

    public function options(School $school): array
    {
        return [];
    }

    public function normalize(mixed $raw): mixed
    {
        if (is_string($raw)) {
            return trim($raw);
        }

        return $raw;
    }

    public function isComplete(School $school, ?int $userId = null): bool
    {
        return OnboardingStepsService::isStepComplete($this->key(), $school, $userId);
    }

    public function required(): bool
    {
        return ! in_array($this->key(), OnboardingStepsService::OPTIONAL_STEPS, true);
    }

    protected function reject(string $message): never
    {
        throw new InvalidArgumentException($message);
    }
}
