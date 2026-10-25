<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Onboarding\Country\SchoolTypePreset;
use App\Onboarding\Steps\AbstractOnboardingStep;
use App\Onboarding\Steps\OnboardingStepAwaitingInput;
use App\Onboarding\Steps\StepIssueCatalog;
use App\Onboarding\Steps\StepIssueException;
use App\Services\OnboardingStepsService;
use InvalidArgumentException;

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
        if (SchoolTypePreset::replyKind($raw) === SchoolTypePreset::ADJUST) {
            return SchoolTypePreset::ADJUST;
        }

        if (is_string($raw) && in_array(strtolower(trim($raw)), ['skip', 'later', 'none', 'n/a'], true)
            && in_array('terms', OnboardingStepsService::OPTIONAL_STEPS, true)) {
            return 'skip';
        }

        if (is_array($raw)) {
            return $raw;
        }

        if ($this->isDefaultTermsReply($raw)) {
            return ['__country_defaults' => true];
        }

        return [];
    }

    public function inputIssues(School $school, mixed $raw): array
    {
        $normalized = $this->resolvedOrIssue($school, $raw);
        if (isset($normalized['issue'])) {
            return [$normalized['issue']];
        }

        return $this->issues($school, $normalized['value']);
    }

    public function issues(School $school, mixed $normalized): array
    {
        if ($normalized === SchoolTypePreset::ADJUST) {
            return [];
        }

        return parent::issues($school, $normalized);
    }

    public function allIssues(School $school, mixed $raw): array
    {
        $normalized = $this->resolvedOrIssue($school, $raw);
        if (isset($normalized['issue'])) {
            return [$normalized['issue']];
        }

        return array_values(array_merge(
            $this->issues($school, $normalized['value']),
            $this->saveIssues($school, $normalized['value']),
        ));
    }

    /**
     * @return array{value: mixed, issue?: \App\Onboarding\Steps\StepIssue}
     */
    private function resolvedOrIssue(School $school, mixed $raw): array
    {
        try {
            return ['value' => $this->resolveTerms($school, $raw)];
        } catch (StepIssueException $e) {
            return ['value' => null, 'issue' => $e->issue];
        } catch (InvalidArgumentException $e) {
            return ['value' => null, 'issue' => StepIssueCatalog::fromMessage($this->key(), $e->getMessage())];
        }
    }

    private function isDefaultTermsReply(mixed $raw): bool
    {
        if (SchoolTypePreset::replyKind($raw) === SchoolTypePreset::ACCEPT) {
            return true;
        }

        return is_string($raw)
            && in_array(strtolower(trim($raw)), ['defaults', 'default', 'yes', 'y', 'standard', 'uneb'], true);
    }

    private function isCountryDefaultMarker(mixed $normalized): bool
    {
        return is_array($normalized)
            && ($normalized['__country_defaults'] ?? false) === true
            && count($normalized) === 1;
    }

    private function resolveTerms(School $school, mixed $raw): mixed
    {
        $normalized = $this->normalize($raw);

        if ($normalized === 'skip' || ! $this->isCountryDefaultMarker($normalized)) {
            return $normalized;
        }

        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            $this->reject('Create an academic year before this step.');
        }

        return $this->termsFromProfile($school, $year->calendarYear());
    }

    /**
     * The country's term calendar, in the academic year's calendar year.
     * The first term is current and the rest are next.
     *
     * @return list<array{name: string, start: string, end: string, status: string}>
     */
    private function termsFromProfile(School $school, int $year): array
    {
        $terms = [];
        $category = is_string($school->school_category) ? $school->school_category : null;
        foreach (SchoolTypePreset::termsFor($school->registration_country, $category, $year) as $index => $term) {
            $term['status'] = $index === 0 ? 'current' : 'next';
            $terms[] = $term;
        }

        return $terms;
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->resolveTerms($school, $normalized);
        if ($normalized === SchoolTypePreset::ADJUST) {
            throw new OnboardingStepAwaitingInput(
                'Send the terms you want, or reply Use this. Nothing is saved yet.'
            );
        }
        $this->validate($school, $normalized);
        if ($normalized === 'skip') {
            OnboardingStepsService::markStepSkipped($school, 'terms');

            return;
        }
        $year = AcademicYear::where('school_id', $school->id)->first();
        if (! $year) {
            $this->reject('Create an academic year before this step.');
        }
        $this->engineCall(fn () => $this->engine->saveTerms($school, $year, $normalized));
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->resolveTerms($school, $normalized);
        if ($normalized === SchoolTypePreset::ADJUST) {
            return [
                'action' => 'noop',
                'summary' => 'Nothing will be saved until you send the terms you want.',
                'rows' => [],
            ];
        }
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
        $normalized = $this->resolveTerms($school, $normalized);
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
