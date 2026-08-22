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
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the section.
     */
    public function view(User $user, Section $section)
    {
        return (int) $user->school_id === (int) $section->school_id;
    }

    /**
     * Determine whether the user can create sections.
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the section.
     */
    public function update(User $user, Section $section)
    {
        return (int) $user->school_id === (int) $section->school_id;
    }

    /**
     * Determine whether the user can delete the section.
     */
    public function delete(User $user, Section $section)
    {
        return (int) $user->school_id === (int) $section->school_id;
    }

    /**
     * Determine whether the user can restore the section.
     */
    public function restore(User $user, Section $section)
    {
        return (int) $user->school_id === (int) $section->school_id;
    }

    /**
     * Determine whether the user can permanently delete the section.
     */
    public function forceDelete(User $user, Section $section)
    {
        return (int) $user->school_id === (int) $section->school_id;
    }
}
