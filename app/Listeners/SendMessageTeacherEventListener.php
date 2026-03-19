<?php

namespace App\Listeners;

use App\Events\SendMessageTeacherEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Traits\SendMessageProcess;
use App\Traits\LogActivity;
use App\Traits\Common;
use App\Models\User;

class SendMessageTeacherEventListener implements ShouldQueue
{
    use SendMessageProcess;
    use LogActivity;
    use Common;
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
     * @param  SendMessageTeacherEvent  $event
     * @return void
     */
    public function handle(SendMessageTeacherEvent $event)
    {
        //
        foreach($event->data->selected as $user_id)
        {
            $user = User::where('id',$user_id)->first();
            $send = $this->selectSendMessage($event->data , $event->school_id , $event->admin_email , $user , $event->admin,$user);
        }
    }
}