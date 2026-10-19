<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Early-adopter invite. Contains a one-time setup link, never a password.
 */
class SchoolInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;

    public string $schoolName;

    public string $inviteUrl;

    public Carbon $expiresAt;

    public function __construct(
        string $name,
        string $schoolName,
        string $inviteUrl,
        Carbon $expiresAt,
    ) {
        $this->name = $name;
        $this->schoolName = $schoolName;
        $this->inviteUrl = $inviteUrl;
        $this->expiresAt = $expiresAt;
    }

    public function build()
    {
        return $this->from('connect@klassapp.xyz', config('mail.from.name'))
            ->subject("You're invited to set up {$this->schoolName} on KlassApp")
            ->markdown('emails.school-invite');
    }
}
