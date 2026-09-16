<?php

namespace App\Observers;

use App\Helpers\DashboardCache;
use Illuminate\Support\Facades\Cache;
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
        $this->forgetLists($user);
    }

    /**
     * Handle the user "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        $this->forgetLists($user);
    }

    /**
     * Handle the user "deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        $this->forgetLists($user);
    }

    /**
     * Handle the user "restored" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function restored(User $user)
    {
        $this->forgetLists($user);
    }

    /**
     * Handle the standard link "force deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        $this->forgetLists($user);
    }

    private function forgetLists(User $user): void
    {
        Cache::forget('parent_list'.$user->school_id);
        Cache::forget('standardLink'.$user->school_id);
        if ($user->studentAcademicLatest) {
            Cache::forget('class_students_'.$user->studentAcademicLatest->standardLink_id);
            Cache::forget('class_student_count'.$user->studentAcademicLatest->standardLink_id);
        }

        // Roster KPI cards (students/teachers/parents) — never leave stale forever.
        DashboardCache::forgetRosterCounts(
            $user->school_id !== null ? (int) $user->school_id : null
        );
    }
}
