<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\AdmissionApprovalEvent;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdmissionApprovalMail;

class AdmissionApprovalListener implements ShouldQueue
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
     * @param  AdmissionApprovalEvent  $event
     * @return void
     */
    public function handle(AdmissionApprovalEvent $event)
    {
        //
        Mail::to($event->data['email'])->queue(new AdmissionApprovalMail($event->data));
    }
}