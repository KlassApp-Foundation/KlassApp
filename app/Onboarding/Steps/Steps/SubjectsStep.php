<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Subject;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Onboarding\Steps\OnboardingStepReview;
use App\Services\OnboardingStepsService;

class SubjectsStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'subjects';
    }

    public function question(): string
    {
        return 'Confirm subjects for your classes.';
    }

    public function inputType(): string
    {
        return 'list';
    }

    public function normalize(mixed $raw): mixed
    {
        if (is_string($raw) && in_array(strtolower(trim($raw)), ['skip', 'later', 'none', 'n/a'], true)
            && in_array('subjects', OnboardingStepsService::OPTIONAL_STEPS, true)) {
            return 'skip';
        }

        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && in_array(strtolower(trim($raw)), ['done', 'yes', 'y', 'confirm', 'ok'], true)) {
            return 'confirm_seeded';
        }

        return [];
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        if ($normalized === 'skip') {
            OnboardingStepsService::markStepSkipped($school, 'subjects');
            OnboardingStepReview::mark($school, 'subjects');

            return;
        }
        if ($normalized === 'confirm_seeded') {
            OnboardingStepReview::mark($school, 'subjects');

            return;
        }
        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            $this->reject('Create an academic year before this step.');
        }
        $this->engineCall(fn () => $this->engine->saveSubjects($school, $year, $normalized));
        OnboardingStepReview::mark($school, 'subjects');
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        if ($normalized === 'skip') {
            return ['action' => 'skip', 'summary' => 'The subjects step will be marked as skipped.', 'rows' => []];
        }

        if ($normalized === 'confirm_seeded') {
            $count = Subject::where('school_id', $school->id)->count();

            return [
                'action' => 'noop',
                'summary' => "Keeping the existing subjects ({$count} rows) — nothing will change.",
                'rows' => [['label' => 'Existing subjects', 'status' => 'already_present', 'detail' => "{$count} subject rows"]],
            ];
        }

        $year = AcademicYear::where('school_id', $school->id)->first();
        $rows = [];
        foreach ($normalized as $className => $subjectNames) {
            $className = trim((string) $className);
            $detail = collect(is_array($subjectNames) ? $subjectNames : [])->map(fn ($n) => trim((string) $n))->filter()->implode(', ');

            if ($className === '') {
                $rows[] = ['label' => '(unnamed class)', 'status' => 'skip', 'detail' => $detail !== '' ? $detail : null];
                continue;
            }
            if (! $year) {
                $rows[] = ['label' => $className, 'status' => 'skip', 'detail' => 'needs an academic year first'];
                continue;
            }

            $links = $this->linksForClass($school, $year, $className);
            if ($links->isEmpty()) {
                $rows[] = ['label' => $className, 'status' => 'skip', 'detail' => 'class does not exist yet — will refuse'];
                continue;
            }

            $new = 0;
            $present = 0;
            $subjectNames = is_array($subjectNames) ? $subjectNames : [];
            foreach ($links as $link) {
                foreach ($subjectNames as $subjectName) {
                    $exists = Subject::where('school_id', $school->id)
                        ->where('academic_year_id', $year->id)
                        ->where('standard_id', $link->standard_id)
                        ->where('section_id', $link->section_id)
                        ->where('name', trim((string) $subjectName))
                        ->exists();
                    $exists ? $present++ : $new++;
                }
            }

            $rows[] = [
                'label' => $className,
                'status' => match (true) {
                    $new > 0 && $present > 0 => 'update',
                    $new > 0 => 'create',
                    default => 'already_present',
                },
                'detail' => trim(($detail !== '' ? $detail.' — ' : '')."{$present} present, {$new} to add"),
            ];
        }

        $anyCreate = (bool) array_filter($rows, fn ($r) => in_array($r['status'], ['create', 'update'], true));
        $anySkip = (bool) array_filter($rows, fn ($r) => $r['status'] === 'skip');

        return [
            'action' => $anyCreate ? 'change' : ($anySkip ? 'skip' : 'noop'),
            'summary' => $anyCreate
                ? 'New subject rows will be created; existing ones stay untouched.'
                : 'All listed subjects already exist — nothing will change.',
            'rows' => $rows,
        ];
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        if ($normalized === 'skip') {
            OnboardingStepsService::markStepSkipped($school, 'subjects');
            OnboardingStepReview::mark($school, 'subjects');

            return ['created' => [], 'skipped' => [['label' => 'subjects', 'reason' => 'skipped']]];
        }

        if ($normalized === 'confirm_seeded') {
            OnboardingStepReview::mark($school, 'subjects');

            return ['created' => [], 'skipped' => [['label' => 'subjects', 'reason' => 'already present']]];
        }

        $before = Subject::where('school_id', $school->id)->count();
        $this->save($school, $normalized, $userId);
        $after = Subject::where('school_id', $school->id)->count();

        if ($after === $before) {
            return ['created' => [], 'skipped' => [['label' => 'subjects', 'reason' => 'already present']]];
        }

        return ['created' => [['subject_rows_created' => $after - $before]], 'skipped' => []];
    }

    /** Same resolution the engine uses: real section records, including naming variants. */
    private function linksForClass(School $school, AcademicYear $year, string $className): \Illuminate\Support\Collection
    {
        return $this->engine->standardLinksForClass($school, $year, $className);
    }
}
