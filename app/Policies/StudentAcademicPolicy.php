<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\StudentAcademic;
use App\Models\User;

class StudentAcademicPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any student academics.
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
     * Determine whether the user can view the student academic.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StudentAcademic  $studentAcademic
     * @return mixed
     */
    public function view(User $user, StudentAcademic $studentAcademic)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can create student academics.
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
     * Determine whether the user can update the student academic.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StudentAcademic  $studentAcademic
     * @return mixed
     */
    public function update(User $user, StudentAcademic $studentAcademic)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can delete the student academic.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StudentAcademic  $studentAcademic
     * @return mixed
     */
    public function delete(User $user, StudentAcademic $studentAcademic)
    {
        //
    }

    /**
     * Determine whether the user can restore the student academic.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StudentAcademic  $studentAcademic
     * @return mixed
     */
    public function restore(User $user, StudentAcademic $studentAcademic)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the student academic.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StudentAcademic  $studentAcademic
     * @return mixed
     */
    public function forceDelete(User $user, StudentAcademic $studentAcademic)
    {
        //
    }
}
