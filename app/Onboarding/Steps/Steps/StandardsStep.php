<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
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

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        if ($normalized === 'skip') {
            return ['action' => 'skip', 'summary' => 'The classes step will be marked as skipped.', 'rows' => []];
        }

        if ($normalized === 'confirm_seeded') {
            $count = StandardLink::where('school_id', $school->id)->count();

            return [
                'action' => 'noop',
                'summary' => "Keeping the existing classes ({$count} class links) — nothing will change.",
                'rows' => [['label' => 'Existing classes', 'status' => 'already_present', 'detail' => "{$count} class links"]],
            ];
        }

        $rows = [];
        $year = AcademicYear::where('school_id', $school->id)->first();
        foreach ($normalized as $class) {
            $className = trim((string) ($class['name'] ?? ''));
            $streams = is_array($class['streams'] ?? null)
                ? array_values(array_filter(array_map(fn ($s) => trim((string) $s), $class['streams']), fn ($s) => $s !== ''))
                : [];
            $detail = $streams !== [] ? 'Streams: '.implode(', ', $streams) : null;

            if ($className === '') {
                $rows[] = ['label' => '(unnamed class)', 'status' => 'skip', 'detail' => 'needs a name'];
                continue;
            }
            if (! $year) {
                $rows[] = ['label' => $className, 'status' => 'skip', 'detail' => 'needs an academic year first'];
                continue;
            }

            $exists = Section::where('school_id', $school->id)->where('name', $className)->exists();
            $rows[] = ['label' => $className, 'status' => $exists ? 'already_present' : 'create', 'detail' => $detail];
        }

        $createCount = count(array_filter($rows, fn ($r) => $r['status'] === 'create'));
        $skipCount = count(array_filter($rows, fn ($r) => $r['status'] === 'skip'));

        return [
            'action' => $createCount > 0 ? 'change' : ($skipCount > 0 ? 'skip' : 'noop'),
            'summary' => $createCount > 0
                ? "{$createCount} new class(es) will be created; existing ones stay untouched."
                : 'All listed classes already exist — nothing will change.',
            'rows' => $rows,
        ];
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        if ($normalized === 'skip') {
            OnboardingStepsService::markStepSkipped($school, 'standards');

            return ['created' => [], 'skipped' => [['label' => 'classes', 'reason' => 'skipped']]];
        }

        if ($normalized === 'confirm_seeded') {
            return ['created' => [], 'skipped' => [['label' => 'classes', 'reason' => 'already present']]];
        }

        $before = [
            'standards' => Standard::where('school_id', $school->id)->count(),
            'sections' => Section::where('school_id', $school->id)->count(),
            'links' => StandardLink::where('school_id', $school->id)->count(),
            'subjects' => \App\Models\Subject::where('school_id', $school->id)->count(),
        ];
        $this->save($school, $normalized, $userId);
        $after = [
            'standards' => Standard::where('school_id', $school->id)->count(),
            'sections' => Section::where('school_id', $school->id)->count(),
            'links' => StandardLink::where('school_id', $school->id)->count(),
            'subjects' => \App\Models\Subject::where('school_id', $school->id)->count(),
        ];

        $diff = array_sum(array_map(fn ($b, $a) => max(0, $a - $b), $before, $after));
        if ($diff === 0) {
            return ['created' => [], 'skipped' => [['label' => 'classes', 'reason' => 'already present']]];
        }

        return ['created' => [['class_rows_created' => $diff]], 'skipped' => []];
    }
}
