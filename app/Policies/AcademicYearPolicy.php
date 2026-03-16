<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any academic years.
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
     * Determine whether the user can view the academic year.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AcademicYear  $academicYear
     * @return mixed
     */
    public function view(User $user, AcademicYear $academicYear)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can create academic years.
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
     * Determine whether the user can update the academic year.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AcademicYear  $academicYear
     * @return mixed
     */
    public function update(User $user, AcademicYear $academicYear)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can delete the academic year.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AcademicYear  $academicYear
     * @return mixed
     */
    public function delete(User $user, AcademicYear $academicYear)
    {
        //
    }

    /**
     * Determine whether the user can restore the academic year.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AcademicYear  $academicYear
     * @return mixed
     */
    public function restore(User $user, AcademicYear $academicYear)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the academic year.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AcademicYear  $academicYear
     * @return mixed
     */
    public function forceDelete(User $user, AcademicYear $academicYear)
    {
        //
    }
}
