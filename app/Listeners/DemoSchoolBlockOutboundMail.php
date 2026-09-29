<?php

namespace App\Listeners;

use App\Services\DemoSchoolCommsGuard;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;

/**
 * Cancels every outbound email addressed to a demo-school recipient.
 *
 * Laravel's Mailer dispatches MessageSending via events->until(); returning
 * false from this listener stops the message before it reaches the transport.
 */
class DemoSchoolBlockOutboundMail
{
    public function handle(MessageSending $event): bool
    {
        $recipients = [];

        foreach (['getTo', 'getCc', 'getBcc'] as $method) {
            foreach ($event->message->{$method}() as $address) {
                $recipients[] = $address->getAddress();
            }
        }

        foreach ($recipients as $email) {
            if (DemoSchoolCommsGuard::blocksEmail($email)) {
                Log::info('[demo-guard] outbound email blocked for demo school recipient', ['to' => $email]);

                return false;
            }
        }

        return true;
    }
}
