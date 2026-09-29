<?php

namespace App\Mail;

use App\Models\DemoRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Lead alert for a new demo request. Reply-To points at the requester,
 * so replying to the alert goes straight back to the school.
 */
class DemoRequestAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public DemoRequest $demoRequest;

    public function __construct(DemoRequest $demoRequest)
    {
        $this->demoRequest = $demoRequest;

        // Set here (not in build()) so the Reply-To is present as soon as the
        // mailable is queued, matching what leaves the queue.
        $this->replyTo($demoRequest->email, $demoRequest->contact_name);
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('New demo request: ' . $this->demoRequest->school_name)
            ->markdown('emails.demo-request');
    }
}
