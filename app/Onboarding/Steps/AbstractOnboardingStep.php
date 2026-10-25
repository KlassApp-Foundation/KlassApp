<?php

namespace App\Onboarding\Steps;

use App\Models\School;
use App\Onboarding\Steps\Contracts\OnboardingStep;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

abstract class AbstractOnboardingStep implements OnboardingStep
{
    public function __construct(protected OnboardingEngine $engine)
    {
    }

    public function applies(School $school): bool
    {
        return array_key_exists($this->key(), OnboardingStepsService::applicableSteps($school));
    }

    public function options(School $school): array
    {
        return [];
    }

    public function schema(School $school): StepSchema
    {
        return StepSchemaCatalog::for($this->key(), $school, $this->options($school));
    }

    public function normalize(mixed $raw): mixed
    {
        if (is_string($raw)) {
            return trim($raw);
        }

        return $raw;
    }

    public function issues(School $school, mixed $normalized): array
    {
        return $this->schema($school)->issues($school, $normalized);
    }

    public function validate(School $school, mixed $normalized): void
    {
        $issues = $this->issues($school, $normalized);
        if ($issues !== []) {
            throw new StepIssueException($issues[0]);
        }
    }

    /**
     * @return list<StepIssue>
     */
    public function saveIssues(School $school, mixed $normalized): array
    {
        return StepIssueCatalog::saveIssues($this->key(), $school, $normalized);
    }

    /**
     * Normalise first, then validate. A rejection inside normalize() is an issue.
     *
     * @return list<StepIssue>
     */
    public function inputIssues(School $school, mixed $raw): array
    {
        try {
            $normalized = $this->normalize($raw);
        } catch (StepIssueException $e) {
            return [$e->issue];
        } catch (InvalidArgumentException $e) {
            return [StepIssueCatalog::fromMessage($this->key(), $e->getMessage())];
        }

        return $this->issues($school, $normalized);
    }

    /**
     * Normalise, then validation issues plus save-time issues.
     *
     * @return list<StepIssue>
     */
    public function allIssues(School $school, mixed $raw): array
    {
        try {
            $normalized = $this->normalize($raw);
        } catch (StepIssueException $e) {
            return [$e->issue];
        } catch (InvalidArgumentException $e) {
            return [StepIssueCatalog::fromMessage($this->key(), $e->getMessage())];
        }

        return array_values(array_merge(
            $this->issues($school, $normalized),
            $this->saveIssues($school, $normalized),
        ));
    }

    public function isComplete(School $school, ?int $userId = null): bool
    {
        return OnboardingStepsService::isStepComplete($this->key(), $school, $userId);
    }

    public function required(): bool
    {
        return ! in_array($this->key(), OnboardingStepsService::OPTIONAL_STEPS, true);
    }

    protected function reject(string $message, ?int $row = null): never
    {
        throw new StepIssueException(StepIssueCatalog::fromMessage($this->key(), $message, $row));
    }

    /**
     * Run an engine save and attach a StepIssue to a validation failure.
     * The error bag stays the engine's, so the shown message does not change.
     */
    protected function engineCall(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (OnboardingStepValidationException $e) {
            throw $e;
        } catch (ValidationException $e) {
            throw OnboardingStepValidationException::wrap($this->key(), $e);
        }
    }

    /**
     * Save-and-report for steps that only set one school attribute. The
     * underlying save() runs unchanged; the report says whether the watched
     * attribute actually changed, so a rerun with the same answer reports
     * "already present" instead of pretending to create something.
     */
    protected function attributeSaveAndReport(School $school, mixed $normalized, string $attribute, string $label): array
    {
        $before = (string) $school->getAttribute($attribute);
        $this->save($school, $normalized);
        $after = (string) $school->fresh()->getAttribute($attribute);

        if ($before === $after) {
            return ['created' => [], 'skipped' => [['label' => $label, 'reason' => 'already present']]];
        }

        return ['created' => [[$attribute => $after]], 'skipped' => []];
    }

    /**
     * Preview counterpart of attributeSaveAndReport(): describes the change
     * the attribute setter would make, entirely read-only.
     */
    protected function attributePreview(School $school, mixed $normalized, string $attribute, string $label): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $before = (string) $school->getAttribute($attribute);

        if ($before === (string) $normalized) {
            return [
                'action' => 'noop',
                'summary' => "{$label} is already '{$before}'.",
                'rows' => [['label' => $label, 'status' => 'already_present', 'detail' => null]],
            ];
        }

        return [
            'action' => 'change',
            'summary' => "{$label} will change from '{$before}' to '{$normalized}'.",
            'rows' => [['label' => $label, 'status' => 'update', 'detail' => (string) $normalized]],
        ];
    }
}
