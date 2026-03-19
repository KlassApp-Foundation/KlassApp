<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanPolicy
{
    use HandlesAuthorization;
    
    /**
     * Determine whether the user can view any plans.
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
     * Determine whether the user can view the plan.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Plan  $plan
     * @return mixed
     */
    public function view(User $user, Plan $plan)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can create plans.
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
     * Determine whether the user can update the plan.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Plan  $plan
     * @return mixed
     */
    public function update(User $user, Plan $plan)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can delete the plan.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Plan  $plan
     * @return mixed
     */
    public function delete(User $user, Plan $plan)
    {
        //
    }

    /**
     * Determine whether the user can restore the plan.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Plan  $plan
     * @return mixed
     */
    public function restore(User $user, Plan $plan)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the plan.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Plan  $plan
     * @return mixed
     */
    public function forceDelete(User $user, Plan $plan)
    {
        //
    }
}
