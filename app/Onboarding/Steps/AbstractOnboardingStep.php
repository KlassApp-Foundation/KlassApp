<?php

namespace App\Onboarding\Steps;

use App\Models\School;
use App\Onboarding\Steps\Contracts\OnboardingStep;
use App\Services\OnboardingEngine;
use App\Services\OnboardingStepsService;
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

    public function normalize(mixed $raw): mixed
    {
        if (is_string($raw)) {
            return trim($raw);
        }

        return $raw;
    }

    public function isComplete(School $school, ?int $userId = null): bool
    {
        return OnboardingStepsService::isStepComplete($this->key(), $school, $userId);
    }

    public function required(): bool
    {
        return ! in_array($this->key(), OnboardingStepsService::OPTIONAL_STEPS, true);
    }

    protected function reject(string $message): never
    {
        throw new InvalidArgumentException($message);
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
