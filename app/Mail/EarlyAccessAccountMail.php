<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent only to an address that already belongs to an active account.
 * The public early-access page does not say whether that was the case.
 */
class EarlyAccessAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $loginUrl,
        public string $resetUrl,
    ) {}

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Your KlassApp account')
            ->markdown('emails.early-access-account');
    }
}
