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
        //
        try
        {
            $name = strtolower($userprofile->firstname) . $userprofile->user_id . rand(1,10);
            User::where('id', $userprofile->user_id)->update(['name' => $name]);
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
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
