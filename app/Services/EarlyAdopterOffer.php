<?php

namespace App\Services;

use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use RuntimeException;

/**
 * Complimentary Growth window for a school that claims an early-adopter invite.
 *
 * Writes the existing CurrentPlan (the plan the school is on) and Subscription
 * (the dated window). The fact that the school came from this offer, and the
 * claim date, are stored on the school via school_details. Nothing here calls
 * a payment provider. Applying again does not move the end date.
 */
class EarlyAdopterOffer
{
    public const OFFER_KEY = 'early_adopter_offer';

    public const OFFER_AT_KEY = 'early_adopter_offer_at';

    public const OFFER_VALUE = 'growth_3_months';

    public const MONTHS = 3;

    public function apply(School $school, User $admin, Carbon $claimedAt): void
    {
        if ($this->offerRecordedAt($school) !== null) {
            return;
        }

        $plan = $this->growthPlan();
        if ($plan === null) {
            throw new RuntimeException('The Growth plan is not available, so the early adopter offer was not applied.');
        }

        CurrentPlan::updateOrCreate(
            ['school_id' => $school->id],
            [
                'plan_id' => $plan->id,
                'status' => 'running',
                'is_trial' => false,
                'trial_started_at' => null,
                'trial_ends_at' => null,
            ]
        );

        $window = [
            'plan_id' => $plan->id,
            'status' => 'approved',
            'start_date' => $claimedAt->toDateString(),
            'end_date' => $claimedAt->copy()->addMonths(self::MONTHS)->toDateString(),
            'amount_paid' => 0,
            'payment_method' => null,
            'payment_reference' => null,
            'payment_details' => null,
            'plan_details' => [
                'name' => $plan->name,
                'offer' => self::OFFER_VALUE,
                'charged' => false,
            ],
        ];

        $subscription = Subscription::query()
            ->where('school_id', $school->id)
            ->where('user_id', $admin->id)
            ->latest('id')
            ->first();

        if ($subscription) {
            $subscription->update($window);
        } else {
            Subscription::create($window + [
                'school_id' => $school->id,
                'user_id' => $admin->id,
            ]);
        }

        $school->setDetailValue(self::OFFER_KEY, self::OFFER_VALUE);
        $school->setDetailValue(self::OFFER_AT_KEY, $claimedAt->toIso8601String());
    }

    public function growthPlan(): ?Plan
    {
        return Plan::query()
            ->where('is_active', 1)
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['growth'])
                    ->orWhereRaw('LOWER(display_name) = ?', ['growth']);
            })
            ->orderBy('order')
            ->first();
    }

    public function offerRecordedAt(School $school): ?Carbon
    {
        $raw = $school->detailValue(self::OFFER_AT_KEY);
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        return Carbon::parse($raw);
    }

    /**
     * The complimentary Growth window is already on this school. Choosing that
     * same plan again must not start the 30-day trial or move the end date.
     */
    public function alreadyGranted(School $school, int $planId): bool
    {
        if ($this->offerRecordedAt($school) === null) {
            return false;
        }

        $current = CurrentPlan::query()->where('school_id', $school->id)->first();

        return $current !== null && (int) $current->plan_id === $planId;
    }

    public function endDate(School $school, ?int $planId = null): ?Carbon
    {
        $currentQuery = CurrentPlan::query()->where('school_id', $school->id);
        if ($planId !== null) {
            $currentQuery->where('plan_id', $planId);
        }
        $current = $currentQuery->first();
        if ($current && $current->is_trial && $current->trial_ends_at) {
            return Carbon::parse($current->trial_ends_at);
        }

        $subscriptionQuery = Subscription::query()
            ->where('school_id', $school->id)
            ->whereNotNull('end_date');
        if ($planId !== null) {
            $subscriptionQuery->where('plan_id', $planId);
        }
        $end = $subscriptionQuery->latest('id')->value('end_date');

        return $end ? Carbon::parse($end) : null;
    }

    public function isComplimentaryGrowth(School $school, ?Plan $plan): bool
    {
        if ($plan === null || $this->offerRecordedAt($school) === null) {
            return false;
        }

        return strcasecmp((string) $plan->name, 'growth') === 0
            || strcasecmp((string) ($plan->display_name ?? ''), 'growth') === 0;
    }

    /**
     * Text to show next to a plan name. Null when that plan has no end date.
     */
    public function endLabel(School $school, ?Plan $plan = null): ?string
    {
        $end = $this->endDate($school, $plan?->id !== null ? (int) $plan->id : null);
        if ($end === null) {
            return null;
        }

        $formatted = $end->format('j M Y');
        if ($this->isComplimentaryGrowth($school, $plan)) {
            return 'free until '.$formatted;
        }

        return 'until '.$formatted;
    }

    public function profileLine(School $school): ?string
    {
        $current = CurrentPlan::with('plan')->where('school_id', $school->id)->first();
        if (! $current || ! $current->plan) {
            return null;
        }

        $plan = $current->plan;
        $name = trim(ucfirst((string) ($plan->display_name ?: $plan->name)));
        $end = $this->endDate($school, (int) $plan->id);

        if ($this->isComplimentaryGrowth($school, $plan) && $end) {
            return $name.' — free until '.$end->format('j M Y');
        }

        if ((float) $plan->amount > 0) {
            $line = $name.' — $'.number_format((float) $plan->amount).' / '.$plan->cycle.' days';
        } else {
            $line = $name.' — Free';
        }

        if ($end) {
            $line .= ' until '.$end->format('j M Y');
        }

        return $line;
    }
}
