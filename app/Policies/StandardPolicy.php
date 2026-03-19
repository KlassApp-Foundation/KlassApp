<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Standard;
use App\Models\User;

class StandardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any standards.
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
     * Determine whether the user can view the standard.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Standard  $standard
     * @return mixed
     */
    public function view(User $user, Standard $standard)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can create standards.
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
     * Determine whether the user can update the standard.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Standard  $standard
     * @return mixed
     */
    public function update(User $user, Standard $standard)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can delete the standard.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Standard  $standard
     * @return mixed
     */
    public function delete(User $user, Standard $standard)
    {
        //
    }

    /**
     * Determine whether the user can restore the standard.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Standard  $standard
     * @return mixed
     */
    public function restore(User $user, Standard $standard)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the standard.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Standard  $standard
     * @return mixed
     */
    public function forceDelete(User $user, Standard $standard)
    {
        //
    }
}
