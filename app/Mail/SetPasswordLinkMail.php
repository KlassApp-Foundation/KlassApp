<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Set-password email. Contains a signed link, never a password.
 */
class SetPasswordLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;

    public string $url;

    public Carbon $expiresAt;

    public int $minutes;

    public function __construct(string $name, string $url, Carbon $expiresAt, int $minutes)
    {
        $this->name = $name;
        $this->url = $url;
        $this->expiresAt = $expiresAt;
        $this->minutes = $minutes;
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Set your KlassApp password')
            ->markdown('emails.set-password-link');
    }
}
