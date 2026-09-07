<?php

namespace App\Observers;

use App\Models\Userprofile;
use App\Models\User;
use Exception;

class UserprofileObserver
{
    /**
     * Handle the userprofile "created" event.
     *
     * @param  \App\Models\Userprofile  $userprofile
     * @return void
     */
    public function created(Userprofile $userprofile)
    {
        // users.name is the human name set at User::create (teacher/student/parent).
        // Historically this observer overwrote it to firstname+user_id+rand (e.g. "sarah631")
        // for URL-slug uniqueness — but users_name_unique was dropped (2026_03_19) so full
        // names are allowed, and credential UIs / agent checks still read users.name as the
        // "username". Never inject digit suffixes here. Display names still come from
        // userprofiles.firstname + lastname via User::displayName / full_name.
        //
        // Only fill an empty name (legacy creates that omit it).
        try {
            $user = User::query()->find($userprofile->user_id);
            if (! $user) {
                return;
            }

            $current = trim((string) $user->name);
            if ($current !== '') {
                return;
            }

            $fromProfile = trim(implode(' ', array_filter([
                trim((string) $userprofile->firstname),
                trim((string) $userprofile->lastname),
            ])));

            if ($fromProfile === '') {
                return;
            }

            $user->update(['name' => $fromProfile]);
        } catch (Exception $e) {
            //
        }
    }

    /**
     * Handle the userprofile "updated" event.
     *
     * @param  \App\Models\Userprofile  $userprofile
     * @return void
     */
    public function updated(Userprofile $userprofile)
    {
        //
    }

    /**
     * Handle the userprofile "deleted" event.
     *
     * @param  \App\Models\Userprofile  $userprofile
     * @return void
     */
    public function deleted(Userprofile $userprofile)
    {
        //
    }

    /**
     * Handle the userprofile "restored" event.
     *
     * @param  \App\Models\Userprofile  $userprofile
     * @return void
     */
    public function restored(Userprofile $userprofile)
    {
        //
    }

    /**
     * Handle the userprofile "force deleted" event.
     *
     * @param  \App\Models\Userprofile  $userprofile
     * @return void
     */
    public function forceDeleted(Userprofile $userprofile)
    {
        //
    }
}
