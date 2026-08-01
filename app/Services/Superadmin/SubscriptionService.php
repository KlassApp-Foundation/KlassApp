<?php

namespace App\Services\Superadmin;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Shared subscription mutators for Livewire SubscriptionForm / Subscriptions table
 * and Toshi platform tools. Mirrors Batch A submitSubscription + Filament approve.
 */
class SubscriptionService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): Subscription
    {
        $validated = $this->validate($data);

        return Subscription::create($validated);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): Subscription
    {
        $subscription = Subscription::findOrFail($id);
        $validated = $this->validate($data);
        $subscription->update($validated);

        return $subscription->fresh();
    }

    /**
     * Approve a pending subscription (Filament Subscriptions approve action).
     *
     * @throws ValidationException
     */
    public function approve(int $id): Subscription
    {
        $subscription = Subscription::findOrFail($id);

        if ($subscription->status !== 'pending') {
            throw ValidationException::withMessages([
                'id' => "Subscription id={$id} is not pending (status={$subscription->status}).",
            ]);
        }

        $startDate = Carbon::today();

        $subscription->update([
            'status' => 'approved',
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonth(),
        ]);

        return $subscription->fresh();
    }

    /**
     * Cancel a subscription (status → canceled). Prefer over free-form status edits.
     *
     * @throws ValidationException
     */
    public function cancel(int $id): Subscription
    {
        $subscription = Subscription::findOrFail($id);

        if ($subscription->status === 'canceled') {
            throw ValidationException::withMessages([
                'id' => "Subscription id={$id} is already canceled.",
            ]);
        }

        $subscription->update([
            'status' => 'canceled',
        ]);

        return $subscription->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validate(array $data): array
    {
        return Validator::make($data, [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'status' => ['required', Rule::in(['pending', 'approved', 'canceled', 'expired'])],
            'payment_details' => ['nullable'],
            'plan_details' => ['nullable'],
            'amount_paid' => ['nullable', 'numeric'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ])->validate();
    }
}
