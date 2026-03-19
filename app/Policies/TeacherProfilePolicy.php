<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\TeacherProfile;
use App\Models\User;

class TeacherProfilePolicy
{
    use HandlesAuthorization;
    
    /**
     * Determine whether the user can view any teacher profiles.
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
     * Determine whether the user can view the teacher profile.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TeacherProfile  $teacherProfile
     * @return mixed
     */
    public function view(User $user, TeacherProfile $teacherProfile)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can create teacher profiles.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the teacher profile.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TeacherProfile  $teacherProfile
     * @return mixed
     */
    public function update(User $user, TeacherProfile $teacherProfile)
    {
        //
        return true;
    }

    /**
     * Determine whether the user can delete the teacher profile.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TeacherProfile  $teacherProfile
     * @return mixed
     */
    public function delete(User $user, TeacherProfile $teacherProfile)
    {
        //
    }

    /**
     * Determine whether the user can restore the teacher profile.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TeacherProfile  $teacherProfile
     * @return mixed
     */
    public function restore(User $user, TeacherProfile $teacherProfile)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the teacher profile.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\TeacherProfile  $teacherProfile
     * @return mixed
     */
    public function forceDelete(User $user, TeacherProfile $teacherProfile)
    {
        //
    }
}
