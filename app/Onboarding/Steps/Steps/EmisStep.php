<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Services\OnboardingStepsService;

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
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engine->saveEmis($school, (string) $normalized);
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $preview = $this->attributePreview($school, $normalized, 'ministry_code', 'EMIS / ministry code');

        if (! OnboardingStepsService::isUganda($school->registration_country)) {
            return [
                'action' => 'noop',
                'summary' => 'No-op here: EMIS / ministry codes are only required for schools registered in Uganda.',
                'rows' => [['label' => 'EMIS / ministry code', 'status' => 'skip', 'detail' => 'only applies to Ugandan schools']],
            ];
        }

        return $preview;
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        return $this->attributeSaveAndReport($school, $normalized, 'ministry_code', 'EMIS / ministry code');
    }
}
