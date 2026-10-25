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
        return match ($this->validation['kind'] ?? '') {
            'filled_string' => is_string($normalized) && trim($normalized) !== '',
            'min_string' => is_string($normalized) && strlen(trim($normalized)) >= (int) $this->validation['min'],
            'any_string' => is_string($normalized),
            'one_of' => is_string($normalized) && in_array($normalized, $this->optionValues(), true),
            'academic_year' => $this->acceptsAcademicYear($normalized),
            'list' => $this->acceptsList($school, $normalized),
            'fees' => $this->acceptsFees($normalized),
            'active_plan' => $this->acceptsActivePlan($normalized),
            default => false,
        };
    }

    private function acceptsAcademicYear(mixed $normalized): bool
    {
        if (! is_array($normalized)) {
            return false;
        }

        $name = trim((string) ($normalized['name'] ?? ''));
        $fromChat = ! empty($normalized['free_text']);

        if ($name === '') {
            return false;
        }

        if ($fromChat && ! $this->isYearLabel($name)) {
            return false;
        }

        return true;
    }

    private function acceptsList(School $school, mixed $normalized): bool
    {
        if ($normalized === 'skip') {
            return ($this->validation['allow_skip'] ?? false) === true
                && in_array($this->key, OnboardingStepsService::OPTIONAL_STEPS, true);
        }

        if ($normalized === 'confirm_seeded') {
            return match ($this->validation['confirm_seeded'] ?? null) {
                'standards_link' => StandardLink::where('school_id', $school->id)->exists(),
                'subjects' => Subject::where('school_id', $school->id)->exists(),
                default => false,
            };
        }

        return is_array($normalized);
    }

    private function acceptsFees(mixed $normalized): bool
    {
        if (! is_array($normalized) || $normalized === []) {
            return false;
        }

        $nameRules = $this->childRules('fees', 'name');
        $amountRules = $this->childRules('fees', 'amount');
        if (! in_array('required', $nameRules, true) || ! in_array('gt:0', $amountRules, true)) {
            return false;
        }

        foreach ($normalized as $fee) {
            if (! is_array($fee)) {
                return false;
            }
            if (trim((string) ($fee['name'] ?? '')) === '') {
                return false;
            }
            $amount = $fee['amount'] ?? null;
            if (! is_numeric($amount) || (float) $amount <= 0) {
                return false;
            }
        }

        return true;
    }

    private function acceptsActivePlan(mixed $normalized): bool
    {
        if (! is_int($normalized) || $normalized < 1) {
            return false;
        }

        return Plan::query()->where('id', $normalized)->where('is_active', 1)->exists();
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
