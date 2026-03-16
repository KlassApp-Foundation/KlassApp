<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any sections.
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
     * Determine whether the user can view the section.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Section  $section
     * @return mixed
     */
    public function view(User $user, Section $section)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can create sections.
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
     * Determine whether the user can update the section.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Section  $section
     * @return mixed
     */
    public function update(User $user, Section $section)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can delete the section.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Section  $section
     * @return mixed
     */
    public function delete(User $user, Section $section)
    {
        //
    }

    /**
     * Determine whether the user can restore the section.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Section  $section
     * @return mixed
     */
    public function restore(User $user, Section $section)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the section.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Section  $section
     * @return mixed
     */
    public function forceDelete(User $user, Section $section)
    {
        //
    }
}
