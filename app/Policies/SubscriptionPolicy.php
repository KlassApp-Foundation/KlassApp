<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionPolicy
{
    use HandlesAuthorization;
    
    /**
     * Determine whether the user can view any subscriptions.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can view the subscription.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Subscription  $subscription
     * @return mixed
     */
    public function view(User $user, Subscription $subscription)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can create subscriptions.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can update the subscription.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Subscription  $subscription
     * @return mixed
     */
    public function update(User $user, Subscription $subscription)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can delete the subscription.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Subscription  $subscription
     * @return mixed
     */
    public function delete(User $user, Subscription $subscription)
    {
        //
    }

    /**
     * Determine whether the user can restore the subscription.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Subscription  $subscription
     * @return mixed
     */
    public function restore(User $user, Subscription $subscription)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the subscription.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Subscription  $subscription
     * @return mixed
     */
    public function forceDelete(User $user, Subscription $subscription)
    {
        //
    }
}
