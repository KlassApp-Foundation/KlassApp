<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use App\Helpers\SiteHelper;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the user "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        //
        Cache::forget('parent_list'.$user->school_id);
        Cache::forget('standardLink'.$user->school_id);
        Cache::forget('class_students_'.$user->studentAcademicLatest->standardLink_id);
        Cache::forget('class_student_count'.$user->studentAcademicLatest->standardLink_id);
    }

    /**
     * Handle the user "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        //
        Cache::forget('parent_list'.$user->school_id);
        Cache::forget('standardLink'.$user->school_id); 
        Cache::forget('class_students_'.$user->studentAcademicLatest->standardLink_id); 
        Cache::forget('class_student_count'.$user->studentAcademicLatest->standardLink_id); 
    }

    /**
     * Handle the user "deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        //
    }

    /**
     * Handle the user "restored" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function restored(User $user)
    {
        //
    }

    /**
     * Handle the standard link "force deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        //
    }
}