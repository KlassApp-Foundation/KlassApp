<?php

namespace App\Listeners;

use App\Events\AbsentReminderMailEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\AbsentReminderMail;

class AbsentReminderMailEventListener implements ShouldQueue
{
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
     * @param  AbsentReminderMailEvent  $event
     * @return void
     */
    public function handle(AbsentReminderMailEvent $event)
    {
        //
        Mail::to($event->reminder->to)->queue(new AbsentReminderMail($event->reminder));
    }
}