<?php

namespace App\Onboarding\Steps;

use App\Models\Plan;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Services\OnboardingStepsService;
use InvalidArgumentException;

/**
 * Declarative contract for one wizard onboarding step.
 *
 * accepts() follows OnboardingStep::validate() and does not write.
 * saveRules are the stricter checks the engine already runs on save.
 */
final class StepSchema
{
    /**
     * @param  list<StepField>  $fields
     * @param  list<array{value: string, label: string}>  $options
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $validation
     * @param  array<string, list<string>>  $saveRules
     */
    public function __construct(
        public string $key,
        public array $fields,
        public bool $confirm,
        public StepActor $actor,
        public array $defaults,
        public array $options,
        public array $validation,
        public array $saveRules = [],
    ) {}

    public function field(string $name): StepField
    {
        foreach ($this->fields as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }

        throw new InvalidArgumentException("Step [{$this->key}] has no field [{$name}].");
    }

    /**
     * @return list<string>
     */
    public function optionValues(): array
    {
        return array_map(fn (array $option) => (string) $option['value'], $this->options);
    }

    /**
     * Same accept/reject decision as the step's validate(), for an already
     * normalized answer. Nothing is saved.
     */
    public function accepts(School $school, mixed $normalized): bool
    {
        return $this->issues($school, $normalized) === [];
    }

    /**
     * Validation problems for an already-normalized answer.
     * A string plan id is rejected here: options are text, and validate()
     * requires the plan's integer id.
     *
     * @return list<StepIssue>
     */
    public function issues(School $school, mixed $normalized): array
    {
        return match ($this->validation['kind'] ?? '') {
            'filled_string' => $this->filledStringIssues($normalized),
            'min_string' => $this->minStringIssues($normalized),
            'any_string' => $this->anyStringIssues($normalized),
            'one_of' => $this->oneOfIssues($normalized),
            'academic_year' => $this->academicYearIssues($normalized),
            'list' => $this->listIssues($school, $normalized),
            'fees' => $this->feeIssues($normalized),
            'active_plan' => $this->planIssues($normalized),
            default => [StepIssueCatalog::fromMessage($this->key, 'Provide a valid list for this step.')],
        };
    }

    /**
     * Stricter save-time problems. These do not change accepts().
     *
     * @return list<StepIssue>
     */
    public function saveIssues(School $school, mixed $normalized): array
    {
        return StepIssueCatalog::saveIssues($this->key, $school, $normalized);
    }

    /**
     * @return list<StepIssue>
     */
    private function filledStringIssues(mixed $normalized): array
    {
        if (is_string($normalized) && trim($normalized) !== '') {
            return [];
        }

        $cause = match ($this->key) {
            'emis' => 'Enter your EMIS / ministry code.',
            'whatsapp_verify' => 'Enter a WhatsApp phone number.',
            default => 'Enter your real school name.',
        };

        return [StepIssueCatalog::fromMessage($this->key, $cause)];
    }

    /**
     * @return list<StepIssue>
     */
    private function minStringIssues(mixed $normalized): array
    {
        $min = (int) $this->validation['min'];
        if (is_string($normalized) && strlen(trim($normalized)) >= $min) {
            return [];
        }

        return [StepIssueCatalog::fromMessage($this->key, 'Enter a country.')];
    }

    /**
     * @return list<StepIssue>
     */
    private function anyStringIssues(mixed $normalized): array
    {
        if (is_string($normalized)) {
            return [];
        }

        return [StepIssueCatalog::fromMessage($this->key, 'Enter a UNEB centre number or skip.')];
    }

    /**
     * @return list<StepIssue>
     */
    private function oneOfIssues(mixed $normalized): array
    {
        if (is_string($normalized) && in_array($normalized, $this->optionValues(), true)) {
            return [];
        }

        $cause = match ($this->key) {
            'curriculum' => 'Choose a curriculum option.',
            'school_category' => 'Choose a school category.',
            default => 'Choose one of the school size options.',
        };

        return [StepIssueCatalog::fromMessage($this->key, $cause)];
    }

    /**
     * @return list<StepIssue>
     */
    private function academicYearIssues(mixed $normalized): array
    {
        if (! is_array($normalized)) {
            return [StepIssueCatalog::fromMessage($this->key, StepIssueCatalog::academicYearCause())];
        }

        $name = trim((string) ($normalized['name'] ?? ''));
        $fromChat = ! empty($normalized['free_text']);

        if ($name === '' || ($fromChat && ! $this->isYearLabel($name))) {
            return [StepIssueCatalog::fromMessage($this->key, StepIssueCatalog::academicYearCause())];
        }

        return [];
    }

    /**
     * @return list<StepIssue>
     */
    private function listIssues(School $school, mixed $normalized): array
    {
        if ($normalized === 'skip') {
            $allowed = ($this->validation['allow_skip'] ?? false) === true
                && in_array($this->key, OnboardingStepsService::OPTIONAL_STEPS, true);

            return $allowed ? [] : [StepIssueCatalog::fromMessage($this->key, 'This step cannot be skipped.')];
        }

        if ($normalized === 'confirm_seeded') {
            $ok = match ($this->validation['confirm_seeded'] ?? null) {
                'standards_link' => StandardLink::where('school_id', $school->id)->exists(),
                'subjects' => Subject::where('school_id', $school->id)->exists(),
                default => false,
            };
            if ($ok) {
                return [];
            }

            if (($this->validation['confirm_seeded'] ?? null) === null) {
                return [StepIssueCatalog::fromMessage($this->key, 'Provide a valid list for this step.')];
            }

            $cause = $this->key === 'subjects'
                ? 'Add or confirm subjects first.'
                : 'Add or confirm your classes first.';

            return [StepIssueCatalog::fromMessage($this->key, $cause)];
        }

        if (! is_array($normalized)) {
            return [StepIssueCatalog::fromMessage($this->key, 'Provide a valid list for this step.')];
        }

        return [];
    }

    /**
     * @return list<StepIssue>
     */
    private function feeIssues(mixed $normalized): array
    {
        if (! is_array($normalized) || $normalized === []) {
            return [StepIssueCatalog::fromMessage($this->key, 'Add at least one fee structure.')];
        }

        $nameRules = $this->childRules('fees', 'name');
        $amountRules = $this->childRules('fees', 'amount');
        if (! in_array('required', $nameRules, true) || ! in_array('gt:0', $amountRules, true)) {
            return [StepIssueCatalog::fromMessage($this->key, 'Add at least one fee structure.')];
        }

        $issues = [];
        foreach ($normalized as $index => $fee) {
            $row = is_int($index) ? $index : null;
            if (! is_array($fee)) {
                $issues[] = StepIssueCatalog::fromMessage($this->key, 'Add at least one fee structure.', $row);

                continue;
            }
            $name = trim((string) ($fee['name'] ?? ''));
            if ($name === '') {
                $issues[] = StepIssueCatalog::fromMessage($this->key, 'Enter a fee name and an amount.', $row);

                continue;
            }
            $amount = $fee['amount'] ?? null;
            if (! is_numeric($amount) || (float) $amount <= 0) {
                $issues[] = StepIssueCatalog::fromMessage($this->key, StepIssueCatalog::feeAmountCause($name), $row);
            }
        }

        return $issues;
    }

    /**
     * @return list<StepIssue>
     */
    private function planIssues(mixed $normalized): array
    {
        if (is_string($normalized) && preg_match('/^\d+$/', trim($normalized)) === 1) {
            return [StepIssueCatalog::issue(
                'plan.id_type',
                'plan_id',
                null,
                'Choose a plan.',
                'Choose a plan from the list. The plan is saved as its number, not as text.',
            )];
        }

        if (! is_int($normalized) || $normalized < 1) {
            return [StepIssueCatalog::fromMessage($this->key, 'Choose a plan.')];
        }

        if (! Plan::query()->where('id', $normalized)->where('is_active', 1)->exists()) {
            return [StepIssueCatalog::fromMessage($this->key, 'That plan is not available.')];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function childRules(string $parent, string $child): array
    {
        foreach ($this->field($parent)->children as $field) {
            if ($field->name === $child) {
                return $field->rules;
            }
        }

        return [];
    }

    private function isYearLabel(string $text): bool
    {
        $text = $this->canonicalYearLabel($text);
        if (preg_match('/^\d{4}$/', $text)) {
            return $this->plausibleYear((int) $text);
        }
        if (preg_match('/^(\d{4})[\/\-](\d{4})$/', $text, $matches)) {
            $start = (int) $matches[1];
            $end = (int) $matches[2];

            return $this->plausibleYear($start) && $this->plausibleYear($end) && $end > $start;
        }

        return false;
    }

    private function canonicalYearLabel(string $text): string
    {
        $text = trim($text);
        if (preg_match('/^(\d{4})\s*([\/\-])\s*(\d{4})$/', $text, $matches)) {
            return $matches[1].$matches[2].$matches[3];
        }

        return $text;
    }

    private function plausibleYear(int $year): bool
    {
        return $year >= 1990 && $year <= 2200;
    }
}
