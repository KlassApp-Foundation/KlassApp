<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\User;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Services\OnboardingStepsService;

class TeachersStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'teachers';
    }

    public function question(): string
    {
        return 'Add teachers now, or skip for later.';
    }

    public function inputType(): string
    {
        return 'skipable_list';
    }

    public function normalize(mixed $raw): mixed
    {
        if (is_string($raw) && in_array(strtolower(trim($raw)), ['skip', 'later', 'none', 'n/a'], true)
            && in_array('teachers', OnboardingStepsService::OPTIONAL_STEPS, true)) {
            return 'skip';
        }

        if (is_array($raw)) {
            return $raw;
        }

        // Chat path: names alone are not enough (engine requires emails) — reject empty.
        return [];
    }

    public function validate(School $school, mixed $normalized): void
    {
        if ($normalized === 'skip') {
            if (! in_array('teachers', OnboardingStepsService::OPTIONAL_STEPS, true)) {
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
            OnboardingStepsService::markStepSkipped($school, 'teachers');

            return;
        }

        [$todo] = $this->splitDrafts($school, $normalized);
        if ($todo === []) {
            return;
        }

        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            $this->reject('Create an academic year before this step.');
        }
        $this->engine->saveTeachers($school, $year, $todo);
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        if ($normalized === 'skip') {
            return ['action' => 'skip', 'summary' => 'The teachers step will be marked as skipped.', 'rows' => []];
        }

        $rows = [];
        foreach ($normalized as $draft) {
            $name = trim((string) ($draft['name'] ?? ''));
            $email = trim((string) ($draft['email'] ?? ''));
            $label = $name !== '' ? $name.' <'.$email.'>' : 'unnamed teacher';

            if ($name === '' || $email === '') {
                $rows[] = ['label' => $label, 'status' => 'skip', 'detail' => 'needs name and email'];
                continue;
            }

            [$status, $detail] = $this->teacherRowStatus($school, $email);
            $rows[] = ['label' => $label, 'status' => $status, 'detail' => $detail];
        }

        $createCount = count(array_filter($rows, fn ($row) => $row['status'] === 'create'));
        $creating = $createCount > 0;
        $anySkip = (bool) array_filter($rows, fn ($row) => $row['status'] === 'skip');

        return [
            'action' => $creating ? 'change' : ($anySkip ? 'skip' : 'noop'),
            'summary' => $creating
                ? "{$createCount} teacher(s) will be added; rows already at this school stay untouched."
                : ($anySkip
                    ? 'No teacher will be added — check the details column.'
                    : 'Nothing will change — every listed teacher is already at this school.'),
            'rows' => $rows,
        ];
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        if ($normalized === 'skip') {
            OnboardingStepsService::markStepSkipped($school, 'teachers');

            return ['created' => [], 'skipped' => [['label' => 'teachers', 'reason' => 'skipped']]];
        }

        [$todo, $skipped] = $this->splitDrafts($school, $normalized);
        if ($todo === []) {
            return ['created' => [], 'skipped' => $skipped];
        }

        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            $this->reject('Create an academic year before this step.');
        }

        $result = $this->engine->saveTeachers($school, $year, $todo);

        $engineSkipped = array_map(
            fn (array $row): array => [
                'label' => trim((string) ($row['name'] ?? '')) !== ''
                    ? trim((string) $row['name']).' <'.trim((string) ($row['email'] ?? '')).'>'
                    : 'unnamed teacher',
                'reason' => trim((string) ($row['reason'] ?? '')) ?: 'skipped',
            ],
            $result['skipped'] ?? []
        );

        return [
            'created' => $result['created'] ?? [],
            'skipped' => array_merge($skipped, $engineSkipped),
        ];
    }

    /**
     * Split teacher drafts into the rows the engine still has to create and
     * rows that already exist at this school. Rerunning the same roster must
     * not fail or duplicate users — the rerun reports the existing user rows
     * as skipped (structured-answer contract (d)).
     *
     * "Already at this school" means a user row with the same email in THIS
     * school. An email registered under a different school stays in the todo
     * list so the engine's loud refusal (global uniqueness) still applies —
     * that is a data conflict, not a rerun. Drafts the engine skips anyway
     * (no name or email) pass through untouched so the engine's report stays
     * the single authority for those.
     *
     * @param  list<array<string,mixed>>  $drafts
     * @return array{0: list<array<string,mixed>>, 1: list<array{label: string, reason: string}>}
     */
    private function splitDrafts(School $school, array $drafts): array
    {
        $todo = [];
        $alreadyPresent = [];

        foreach ($drafts as $draft) {
            $name = trim((string) ($draft['name'] ?? ''));
            $email = trim((string) ($draft['email'] ?? ''));
            $label = $name !== '' ? $name.' <'.$email.'>' : 'unnamed teacher';

            if ($name === '' || $email === '') {
                $todo[] = $draft;
                continue;
            }

            $presentHere = User::withTrashed()
                ->where('school_id', $school->id)
                ->where('email', $email)
                ->where('usergroup_id', 5)
                ->exists();

            if ($presentHere) {
                $alreadyPresent[] = ['label' => $label, 'reason' => 'already present'];
                continue;
            }

            $todo[] = $draft;
        }

        return [$todo, $alreadyPresent];
    }

    /**
     * @return array{0: string, 1: ?string} status + detail for one teacher draft
     */
    private function teacherRowStatus(School $school, string $email): array
    {
        $presentHere = User::withTrashed()
            ->where('school_id', $school->id)
            ->where('email', $email)
            ->where('usergroup_id', 5)
            ->exists();
        if ($presentHere) {
            return ['already_present', null];
        }

        $taken = User::withTrashed()->where('email', $email)->exists();
        if ($taken) {
            return ['skip', 'the email is already registered to another account — resubmitting would refuse'];
        }

        return ['create', null];
    }
}
