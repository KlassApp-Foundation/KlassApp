<?php

namespace App\Onboarding\Steps\Steps;

use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Onboarding\Steps\AbstractOnboardingStep;

class PlanSelectionStep extends AbstractOnboardingStep
{
    public function key(): string
    {
        return 'plan_selection';
    }

    public function question(): string
    {
        return 'Choose a plan to start with.';
    }

    public function inputType(): string
    {
        return 'plan';
    }

    public function options(School $school): array
    {
        return Plan::query()
            ->where('is_active', 1)
            ->orderBy('order')
            ->get()
            ->map(fn (Plan $p) => [
                'value' => (string) $p->id,
                'label' => (string) ($p->display_name ?: $p->name),
            ])
            ->all();
    }

    public function normalize(mixed $raw): mixed
    {
        if (is_array($raw)) {
            return (int) ($raw['plan_id'] ?? $raw['id'] ?? 0);
        }

        if (is_numeric($raw)) {
            return (int) $raw;
        }

        if (is_string($raw)) {
            $label = strtolower(trim($raw));
            $plan = Plan::query()
                ->where('is_active', 1)
                ->get()
                ->first(function (Plan $p) use ($label) {
                    return strtolower((string) $p->name) === $label
                        || strtolower((string) ($p->display_name ?: '')) === $label;
                });

            return $plan ? (int) $plan->id : 0;
        }

        return 0;
    }

    public function validate(School $school, mixed $normalized): void
    {
        if (! is_int($normalized) || $normalized < 1) {
            $this->reject('Choose a plan.');
        }
        if (! Plan::query()->where('id', $normalized)->where('is_active', 1)->exists()) {
            $this->reject('That plan is not available.');
        }
    }

    public function save(School $school, mixed $normalized, ?int $userId = null): void
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);
        $this->engine->savePlan($school, $normalized, skipCompletionCheck: false, userId: $userId);
    }

    public function preview(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        $plan = Plan::query()->find($normalized);
        $label = $plan ? (string) ($plan->display_name ?: $plan->name) : (string) $normalized;

        $current = CurrentPlan::where('school_id', $school->id)->first();

        if ($current && (int) $current->plan_id === $normalized) {
            return [
                'action' => 'noop',
                'summary' => "The school is already on the {$label} plan.",
                'rows' => [['label' => $label, 'status' => 'already_present', 'detail' => null]],
            ];
        }

        return [
            'action' => 'change',
            'summary' => "The school will be on the {$label} plan.",
            'rows' => [['label' => $label, 'status' => $current ? 'update' : 'create', 'detail' => null]],
        ];
    }

    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array
    {
        $normalized = $this->normalize($normalized);
        $this->validate($school, $normalized);

        $before = CurrentPlan::where('school_id', $school->id)->first();
        $planId = (int) $normalized;

        if ($before && (int) $before->plan_id === $planId) {
            return [
                'created' => [],
                'skipped' => [['label' => (string) $planId, 'reason' => 'already on this plan']],
            ];
        }

        $this->engine->savePlan($school, $planId, skipCompletionCheck: false, userId: $userId);

        return ['created' => [['plan_id' => $planId]], 'skipped' => []];
    }
}
