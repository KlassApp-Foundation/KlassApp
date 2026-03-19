<?php

namespace App\Listeners;

use App\Events\BirthdayPushEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
//use App\Traits\SendPushNotification;
use App\Notifications\SendDeviceNotification;
use App\Notifications\SendTeacherNotification;
class BirthdayPushEventListener implements ShouldQueue
{

    //use SendPushNotification;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  BirthdayPushEvent  $event
     * @return void
     */
    public function handle(BirthdayPushEvent $event)
    {
        //
        // if($user->usergroup_id==7)
        // {
        //     $this->sendNotification($event->queue->data, $event->queue->user->platform_token);
        // }

        // if($user->usergroup_id==5)
        // {
        //     $this->sendTeacherNotification($event->queue->data,$event->queue->user->platform_token);
        // }

            if($user->usergroup_id==5)
            {
                $user->notify(new SendTeacherNotification($event->data,$user->platform_token));
            }

            if($user->usergroup_id==7)
            {
                $user->notify(new SendDeviceNotification($event->data,$user->platform_token));
              
            }
    }
}