<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Services\OnboardingStepsService;

class TermsStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'terms';
    }

    public function question(): string
    {
        return 'Set your academic terms.';
    }

    public function inputType(): string
    {
        return 'list';
    }

    public function normalize(mixed $raw): mixed
    {
        if (is_string($raw) && in_array(strtolower(trim($raw)), ['skip', 'later', 'none', 'n/a'], true)
            && in_array('terms', OnboardingStepsService::OPTIONAL_STEPS, true)) {
            return 'skip';
        }

        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && in_array(strtolower(trim($raw)), ['defaults', 'default', 'yes', 'y', 'standard', 'uneb'], true)) {
            $y = (int) date('Y');

            return [
                ['name' => 'Term 1', 'start' => "{$y}-02-02", 'end' => "{$y}-05-08", 'status' => 'current'],
                ['name' => 'Term 2', 'start' => "{$y}-05-26", 'end' => "{$y}-08-28", 'status' => 'next'],
                ['name' => 'Term 3', 'start' => "{$y}-09-14", 'end' => "{$y}-12-04", 'status' => 'next'],
            ];
        }

        return [];
    }

    public function validate(School $school, mixed $normalized): void
    {
        if ($normalized === 'skip') {
            if (! in_array('terms', OnboardingStepsService::OPTIONAL_STEPS, true)) {
                $this->reject('This step cannot be skipped.');
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
            OnboardingStepsService::markStepSkipped($school, 'terms');

            return;
        }
        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            $this->reject('Create an academic year before this step.');
        }
        $this->engine->saveTerms($school, $year, $normalized);
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        if ($normalized === 'skip') {
            return ['action' => 'skip', 'summary' => 'The terms step will be marked as skipped.', 'rows' => []];
        }

        $rows = [];
        foreach ($normalized as $term) {
            $name = trim((string) ($term['name'] ?? ''));
            $dates = collect([$term['start'] ?? null, $term['end'] ?? null])->filter()->implode(' → ');
            $rows[] = [
                'label' => $name,
                'status' => AcademicTerm::where('school_id', $school->id)->where('name', $name)->exists()
                    ? 'already_present'
                    : 'create',
                'detail' => $dates !== '' ? $dates : null,
            ];
        }

        $createCount = count(array_filter($rows, fn ($r) => $r['status'] === 'create'));

        return [
            'action' => $createCount > 0 ? 'change' : 'noop',
            'summary' => $createCount > 0
                ? "{$createCount} term(s) will be created; existing ones keep their dates."
                : 'All listed terms already exist — nothing will change.',
            'rows' => $rows,
        ];
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        if ($normalized === 'skip') {
            OnboardingStepsService::markStepSkipped($school, 'terms');

            return ['created' => [], 'skipped' => [['label' => 'terms', 'reason' => 'skipped']]];
        }

        $before = AcademicTerm::where('school_id', $school->id)->count();
        $this->save($school, $normalized, $userId);
        $after = AcademicTerm::where('school_id', $school->id)->count();

        if ($after === $before) {
            return ['created' => [], 'skipped' => [['label' => 'terms', 'reason' => 'already present']]];
        }

        return ['created' => [['term_rows_created' => $after - $before]], 'skipped' => []];
    }
}
