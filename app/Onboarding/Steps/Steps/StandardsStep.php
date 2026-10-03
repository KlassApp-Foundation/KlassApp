<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StandardLink;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Services\OnboardingStepsService;

class StandardsStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'standards';
    }

    public function question(): string
    {
        return 'Confirm your classes and streams.';
    }

    public function inputType(): string
    {
        return 'structure';
    }

    public function normalize(mixed $raw): mixed
    {
        if (is_string($raw) && in_array(strtolower(trim($raw)), ['skip', 'later', 'none', 'n/a'], true)
            && in_array('standards', OnboardingStepsService::OPTIONAL_STEPS, true)) {
            return 'skip';
        }

        if (is_array($raw)) {
            return $raw;
        }

        // Already-seeded structure: "done"/"yes" is a no-op confirm (empty list → engine no-op path rejected; use skip marker).
        if (is_string($raw) && in_array(strtolower(trim($raw)), ['done', 'yes', 'y', 'confirm', 'ok'], true)) {
            return 'confirm_seeded';
        }

        return [];
    }

    public function validate(School $school, mixed $normalized): void
    {
        if ($normalized === 'skip') {
            if (! in_array('standards', OnboardingStepsService::OPTIONAL_STEPS, true)) {
                $this->reject('This step cannot be skipped.');
            }

            return;
        }
        if ($normalized === 'confirm_seeded') {
            if (! StandardLink::where('school_id', $school->id)->exists()) {
                $this->reject('Add or confirm your classes first.');
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
            OnboardingStepsService::markStepSkipped($school, 'standards');

            return;
        }
        if ($normalized === 'confirm_seeded') {
            return;
        }
        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            $this->reject('Create an academic year before this step.');
        }
        $this->engine->saveStandards($school, $year, $normalized);
    }
}
