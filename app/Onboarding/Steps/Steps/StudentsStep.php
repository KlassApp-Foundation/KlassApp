<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;

class StudentsStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'students';
    }

    public function question(): string
    {
        return 'Add students now, or skip for later.';
    }

    public function inputType(): string
    {
        return 'skipable_list';
    }

    public function normalize(mixed $raw): mixed
    {
        if (is_string($raw) && in_array(strtolower(trim($raw)), ['skip', 'later', 'none', 'n/a'], true)
            && in_array('students', OnboardingStepsService::OPTIONAL_STEPS, true)) {
            return 'skip';
        }

        if (is_array($raw)) {
            return $raw;
        }

        return [];
    }

    public function validate(School $school, mixed $normalized): void
    {
        if ($normalized === 'skip') {
            if (! in_array('students', OnboardingStepsService::OPTIONAL_STEPS, true)) {
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
            OnboardingStepsService::markStepSkipped($school, 'students');

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
        $this->engine->saveStudents($school, $year, $todo);
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        if ($normalized === 'skip') {
            return ['action' => 'skip', 'summary' => 'The students step will be marked as skipped.', 'rows' => []];
        }

        $rows = [];
        foreach ($normalized as $draft) {
            $name = trim((string) ($draft['name'] ?? ''));
            $className = trim((string) ($draft['class'] ?? ''));
            $stream = trim((string) ($draft['stream'] ?? ''));
            $email = trim((string) ($draft['email'] ?? ''));

            $label = $name !== '' ? $name.($className !== '' ? " ({$className})" : '') : 'unnamed student';

            if ($name === '' || ($className === '' && $email === '')) {
                $rows[] = ['label' => $label, 'status' => 'skip', 'detail' => 'needs a name (and a class or email)'];
                continue;
            }

            if ($this->studentRowPresent($school, $draft)) {
                $rows[] = ['label' => $label, 'status' => 'already_present', 'detail' => null];
                continue;
            }

            $conflict = $this->studentRowConflict($school, $email);
            if ($conflict !== null) {
                $rows[] = ['label' => $label, 'status' => 'skip', 'detail' => $conflict];
                continue;
            }

            $rows[] = ['label' => $label, 'status' => 'create', 'detail' => null];
        }

        $createCount = count(array_filter($rows, fn ($row) => $row['status'] === 'create'));
        $creating = $createCount > 0;
        $anySkip = (bool) array_filter($rows, fn ($row) => $row['status'] === 'skip');

        return [
            'action' => $creating ? 'change' : ($anySkip ? 'skip' : 'noop'),
            'summary' => $creating
                ? "{$createCount} student(s) will be enrolled; rows already at this school stay untouched."
                : ($anySkip
                    ? 'No student will be enrolled — check the details column.'
                    : 'Nothing will change — every listed student is already at this school.'),
            'rows' => $rows,
        ];
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        if ($normalized === 'skip') {
            OnboardingStepsService::markStepSkipped($school, 'students');

            return ['created' => [], 'skipped' => [['label' => 'students', 'reason' => 'skipped']]];
        }

        [$todo, $skipped] = $this->splitDrafts($school, $normalized);
        if ($todo === []) {
            return ['created' => [], 'skipped' => $skipped];
        }

        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            $this->reject('Create an academic year before this step.');
        }

        $result = $this->engine->saveStudents($school, $year, $todo);

        $engineSkipped = array_map(
            fn (array $row): array => [
                'label' => trim((string) ($row['name'] ?? '')) ?: 'unnamed student',
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
     * Split student drafts into the rows the engine still has to enroll and
     * rows already present at this school. Rerunning the same roster must not
     * duplicate or fail — the rerun reports the existing students as skipped
     * (structured-answer contract (d)).
     *
     * A draft "already present" when this school has the same identity, in
     * this order of strength:
     *   - a user with the provided email here (usergroup 6);
     *   - a student_academics row with the provided school_student_id here;
     *   - a student_academics row with the provided LIN;
     *   - same name AND class, when no identifier field was provided
     *     (identifiers are what lets two children share a name).
     * Anything else — including a provided email/LIN registered ANOTHER
     * school/user — stays in the todo list so the engine's loud refusal
     * (global uniqueness) still applies.
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
            $className = trim((string) ($draft['class'] ?? ''));

            $label = $name !== '' ? $name.($className !== '' ? " ({$className})" : '') : 'unnamed student';

            if ($name === '') {
                $todo[] = $draft;
                continue;
            }

            if ($this->studentRowPresent($school, $draft)) {
                $alreadyPresent[] = ['label' => $label, 'reason' => 'already present'];
                continue;
            }

            $todo[] = $draft;
        }

        return [$todo, $alreadyPresent];
    }

    /** Whether this exact identity is already a student at $school. */
    private function studentRowPresent(School $school, array $draft): bool
    {
        $email = trim((string) ($draft['email'] ?? ''));
        $studentId = trim((string) ($draft['school_student_id'] ?? ''));
        $lin = trim((string) ($draft['lin'] ?? $draft['learner_id'] ?? ''));
        $name = trim((string) ($draft['name'] ?? ''));
        $className = trim((string) ($draft['class'] ?? ''));

        if ($email !== '' && User::withTrashed()
            ->where('school_id', $school->id)
            ->where('email', $email)
            ->where('usergroup_id', 6)
            ->exists()) {
            return true;
        }

        if ($studentId !== '' && StudentAcademic::withTrashed()
            ->where('school_id', $school->id)
            ->where('school_student_id', $studentId)
            ->exists()) {
            return true;
        }

        if ($lin !== '' && StudentAcademic::withTrashed()
            ->where('school_id', $school->id)
            ->where('lin', $lin)
            ->exists()) {
            return true;
        }

        if ($className === '') {
            return false;
        }

        // Name + class (no identifiers given): the same child being re-added.
        return StudentAcademic::where('school_id', $school->id)
            ->whereNotNull('standardLink_id')
            ->whereHas('user', fn ($q) => $q->where('name', $name))
            ->whereHas('standardLink.section', fn ($q) => $q->where(
                'name',
                OnboardingEngine::composeClassAndStream($className, (string) ($draft['stream'] ?? ''))
            ))
            ->exists();
    }

    /** Human-readable conflict for a provided email, or null when clean. */
    private function studentRowConflict(School $school, string $email): ?string
    {
        if ($email === '') return null;
        if (User::withTrashed()->where('email', $email)->exists()) {
            return 'the email is already registered to another account — resubmitting would refuse';
        }
        return null;
    }
}
