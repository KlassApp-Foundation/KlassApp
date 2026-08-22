<?php

namespace App\Services;

use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;

/**
 * Assigns a free-tier CurrentPlan once a school has finished the substantive
 * onboarding steps (everything except plan_selection).
 *
 * Used by explicit plan-confirmation paths (and tests). The manual wizard and
 * dashboard must NOT call this automatically before the user sees plan selection —
 * that step is a required visible milestone even while schools remain free.
 *
 * Safety contract — protects existing schools that are currently "unlimited by
 * missing CurrentPlan" from suddenly being blocked:
 *
 *   1. Never touches a school that already has a CurrentPlan.
 *   2. Only assigns once all applicable steps EXCEPT plan_selection are done.
 *   3. Never assigns a plan whose limits are already exceeded by current usage.
 */
class FreeTierPlanService
{
    public function assignIfEligible(School $school, ?int $userId = null): ?CurrentPlan
    {
        if (CurrentPlan::where('school_id', $school->id)->exists()) {
            return null;
        }

        if (! $this->contentOnboardingComplete($school, $userId)) {
            return null;
        }

        $plan = $this->freeTierPlan($school->id);
        if (! $plan) {
            return null;
        }

        if ($this->planWouldBlock($school->id, $plan)) {
            return null;
        }

        return CurrentPlan::create([
            'school_id' => $school->id,
            'plan_id'   => $plan->id,
            'status'    => 'running',
        ]);
    }

    /**
     * True when every applicable onboarding step except plan_selection is done.
     */
    private function contentOnboardingComplete(School $school, ?int $userId): bool
    {
        $next = OnboardingStepsService::nextBlockingIncompleteStep($school, $userId);

        return $next === null || $next['key'] === 'plan_selection';
    }

    /**
     * Prefer an explicit "Freemium" plan; otherwise the smallest active tier
     * that still fits the school's current student count (0 = unlimited).
     */
    private function freeTierPlan(int $schoolId): ?Plan
    {
        $freemium = Plan::query()
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) = ?', ['freemium'])
                    ->orWhereRaw('LOWER(display_name) = ?', ['freemium']);
            })
            ->orderBy('order')
            ->first();

        if ($freemium) {
            return $freemium;
        }

        $students = OnboardingStepsService::countActiveStudents($schoolId);

        return OnboardingStepsService::suggestPlanForStudentCount($students);
    }

    /**
     * Mirrors ToshiActionService::enforcePlanLimit — a limit of 0 means
     * unlimited; students count ug6, teachers ug5, admins ug3, teachers/admins
     * both measured against no_of_users.
     */
    private function planWouldBlock(int $schoolId, Plan $plan): bool
    {
        $studentLimit = (int) ($plan->no_of_students ?? 0);
        $userLimit    = (int) ($plan->no_of_users ?? 0);

        $students = User::where('school_id', $schoolId)->where('usergroup_id', 6)->count();
        $teachers = User::where('school_id', $schoolId)->where('usergroup_id', 5)->count();
        $admins   = User::where('school_id', $schoolId)->where('usergroup_id', 3)->count();

        if ($studentLimit > 0 && $students > $studentLimit) {
            return true;
        }

        if ($userLimit > 0 && ($teachers > $userLimit || $admins > $userLimit)) {
            return true;
        }

        return false;
    }
}
