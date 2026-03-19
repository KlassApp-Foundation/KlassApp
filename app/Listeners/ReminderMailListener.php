<?php

namespace App\Listeners;

use App\Events\ReminderMailEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReminderMail;

class ReminderMailListener implements ShouldQueue
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
     * @param  ReminderMailEvent  $event
     * @return void
     */
    public function handle(ReminderMailEvent $event)
    {
        Mail::to($event->reminder->to)->queue(new ReminderMail($event->reminder));
    }
}