<?php

namespace App\Services;

use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use Carbon\Carbon;

class TrialService
{
    const FREEMIUM_PLAN_ID = 1;
    const TRIAL_DAYS = 30;

    /**
     * Start a trial for a school selecting a paid plan.
     * Creates or updates the CurrentPlan with full paid-tier limits plus trial metadata.
     */
    public static function startTrial(int $schoolId, int $planId): CurrentPlan
    {
        $plan = Plan::findOrFail($planId);

        if ($plan->amount <= 0) {
            throw new \InvalidArgumentException('Cannot start a trial on a free plan.');
        }

        $now = Carbon::now();

        $currentPlan = CurrentPlan::updateOrCreate(
            ['school_id' => $schoolId],
            [
                'plan_id'          => $planId,
                'status'           => 'running',
                'is_trial'         => true,
                'trial_started_at' => $now,
                'trial_ends_at'    => $now->copy()->addDays(self::TRIAL_DAYS),
            ]
        );

        return $currentPlan;
    }

    /**
     * Check if a school is currently in an active trial.
     */
    public static function isActiveTrial(int $schoolId): bool
    {
        $cp = CurrentPlan::where('school_id', $schoolId)->first();

        if (!$cp || !$cp->is_trial || !$cp->trial_ends_at) {
            return false;
        }

        return Carbon::now()->lessThan($cp->trial_ends_at);
    }

    /**
     * Downgrade a school from trial to Freemium plan.
     * Preserves all data — only changes the plan_id and clears trial flags.
     */
    public static function downgradeToFreemium(int $schoolId): ?CurrentPlan
    {
        $cp = CurrentPlan::where('school_id', $schoolId)->first();
        if (!$cp) {
            return null;
        }

        $cp->update([
            'plan_id'          => self::FREEMIUM_PLAN_ID,
            'is_trial'         => false,
            'trial_started_at' => null,
            'trial_ends_at'    => null,
        ]);

        return $cp;
    }

    /**
     * Find all schools whose trial has expired and needs downgrading.
     */
    public static function getExpiredTrials(): \Illuminate\Support\Collection
    {
        return CurrentPlan::where('is_trial', true)
            ->where('trial_ends_at', '<=', Carbon::now())
            ->get();
    }
}
