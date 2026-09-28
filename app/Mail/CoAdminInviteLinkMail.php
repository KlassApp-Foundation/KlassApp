<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Invite email for new co-admins — contains a one-time setup link,
 * NEVER a password.
 */
class CoAdminInviteLinkMail extends Mailable
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
        Carbon $expiresAt = null,
    ) {
        $this->name = $name;
        $this->schoolName = $schoolName;
        $this->inviteUrl = $inviteUrl;
        $this->expiresAt = $expiresAt ?? now()->addHours(72);
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject("You're invited as Co-Admin at {$this->schoolName}")
            ->markdown('emails.co-admin-invite-link');
    }
}
