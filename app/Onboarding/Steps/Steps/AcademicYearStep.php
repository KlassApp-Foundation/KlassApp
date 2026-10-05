<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\AcademicYear;
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

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        $name = (string) $normalized['name'];
        $existing = AcademicYear::where('school_id', $school->id)->first();
        $dates = collect([$normalized['start'] ?? null, $normalized['end'] ?? null])->filter()->implode(' → ');

        if ($existing && (string) $existing->name === $name) {
            return [
                'action' => 'noop',
                'summary' => "Academic year '{$name}' is already set for this school.",
                'rows' => [['label' => $name, 'status' => 'already_present', 'detail' => $dates !== '' ? $dates : null]],
            ];
        }

        return [
            'action' => 'change',
            'summary' => $existing
                ? "Academic year will change from '{$existing->name}' to '{$name}'."
                : "Academic year '{$name}' will be created.",
            'rows' => [['label' => $name, 'status' => $existing ? 'update' : 'create', 'detail' => $dates !== '' ? $dates : null]],
        ];
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        $existing = AcademicYear::where('school_id', $school->id)->first();
        $year = $this->engine->saveAcademicYear(
            $school,
            (string) $normalized['name'],
            $normalized['start'] ?? null,
            $normalized['end'] ?? null
        );

        if ($existing) {
            return ['created' => [], 'skipped' => [['label' => (string) $year->name, 'reason' => 'already present']]];
        }

        return ['created' => [['academic_year_id' => $year->id, 'name' => (string) $year->name]], 'skipped' => []];
    }
}
