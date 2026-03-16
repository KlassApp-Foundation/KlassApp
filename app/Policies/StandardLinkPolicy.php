<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\StandardLink;
use App\Models\User;

class StandardLinkPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any standard links.
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
     * Determine whether the user can view the standard link.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StandardLink  $standardLink
     * @return mixed
     */
    public function view(User $user, StandardLink $standardLink)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can create standard links.
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
     * Determine whether the user can update the standard link.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StandardLink  $standardLink
     * @return mixed
     */
    public function update(User $user, StandardLink $standardLink)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can delete the standard link.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StandardLink  $standardLink
     * @return mixed
     */
    public function delete(User $user, StandardLink $standardLink)
    {
        //
    }

    /**
     * Determine whether the user can restore the standard link.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StandardLink  $standardLink
     * @return mixed
     */
    public function restore(User $user, StandardLink $standardLink)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the standard link.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StandardLink  $standardLink
     * @return mixed
     */
    public function forceDelete(User $user, StandardLink $standardLink)
    {
        //
    }
}
