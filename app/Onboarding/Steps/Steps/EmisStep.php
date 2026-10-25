<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\School;
use App\Onboarding\Country\CountryProfiles;
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

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engineCall(fn () => $this->engine->saveEmis($school, (string) $normalized));
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $preview = $this->attributePreview($school, $normalized, 'ministry_code', 'EMIS / ministry code');

        if (! CountryProfiles::requiresMinistryCode($school->registration_country)) {
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
