<?php

namespace App\Listeners;

use App\Events\VerificationMailEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailVerification;

class VerificationMailEventListener
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
     * @param  VerificationMailEvent  $event
     * @return void
     */
    public function handle(VerificationMailEvent $event)
    {
        //
        Mail::to($event->user->email)->queue(new EmailVerification($event->user));
    }
}