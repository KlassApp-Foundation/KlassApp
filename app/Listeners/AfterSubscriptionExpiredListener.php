<?php

namespace App\Listeners;

use App\Events\AfterSubscriptionExpiredEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\AfterSubscriptionExpiredMail;

class AfterSubscriptionExpiredListener implements ShouldQueue
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
     * @param  AfterSubscriptionExpiredEvent  $event
     * @return void
     */
    public function handle(AfterSubscriptionExpiredEvent $event)
    {
        //
        Mail::to($event->subscription->user->email)->queue(new AfterSubscriptionExpiredMail($event->subscription));
    }
}