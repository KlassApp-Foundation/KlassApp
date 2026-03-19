<?php

namespace App\Listeners;

use App\Events\AfterExpiredEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionExpiredMail;

class AfterExpiredListener implements ShouldQueue
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
     * @param  AfterExpiredEvent  $event
     * @return void
     */
    public function handle(AfterExpiredEvent $event)
    {
        //
        Mail::to($event->subscription->user->email)->queue(new SubscriptionExpiredMail($event->subscription));
    }
}