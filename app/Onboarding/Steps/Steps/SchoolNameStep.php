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

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $name = trim((string) $normalized);

        if ((string) $school->name === $name) {
            return [
                'action' => 'noop',
                'summary' => "School name is already '{$name}'.",
                'rows' => [['label' => 'School name', 'status' => 'already_present', 'detail' => null]],
            ];
        }

        return [
            'action' => 'change',
            'summary' => "School name will change from '{$school->name}' to '{$name}'.",
            'rows' => [['label' => 'School name', 'status' => 'update', 'detail' => $name]],
        ];
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        return $this->attributeSaveAndReport($school, $normalized, 'name', 'School name');
    }
}
