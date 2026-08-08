<?php

namespace App\Services;

use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;

/**
 * Auto-assigns a free-tier CurrentPlan once a school has finished the
 * substantive onboarding steps (everything except plan_selection), so the
 * plan-selection UI becomes informational / non-blocking rather than a gate.
 *
 * Safety contract — protects the large body of existing schools that are
 * currently "unlimited by missing CurrentPlan" from suddenly being blocked:
 *
 *   1. Never touches a school that already has a CurrentPlan.
 *   2. Only assigns once all applicable steps EXCEPT plan_selection are done
 *      (a "real onboarded state").
 *   3. Never assigns a plan whose limits are already exceeded by the school's
 *      current students / teachers / admins. Such a school is left plan-less
 *      (i.e. effectively unlimited) exactly as it was before this feature.
 *
 * Rule 3 is the key guarantee: assigning a free tier can only ever keep a
 * school where it is or give it MORE headroom — it can never shrink an
 * already-populated school below its current usage.
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
        foreach (OnboardingStepsService::incompleteSteps($school, $userId) as $step) {
            if ($step['key'] !== 'plan_selection') {
                return false;
            }
        }

        return true;
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
