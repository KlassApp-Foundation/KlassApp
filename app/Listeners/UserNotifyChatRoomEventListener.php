<?php

namespace App\Listeners;

use App\Events\UserNotifyChatRoomEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Traits\ReminderProcess;

class UserNotifyChatRoomEventListener implements ShouldQueue
{
    use ReminderProcess;
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
     * @param  UserNotifyChatRoomEvent  $event
     * @return void
     */
    public function handle(UserNotifyChatRoomEvent $event)
    {
        //
        $this->createReminder($event->school_id,$event->from,$event->mobile_no,$event->subject,$event->message,$event->entity_id,$event->entity_name,$event->via,$event->data,$event->executed_at);
    }
}