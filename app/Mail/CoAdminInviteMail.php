<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Co-admin notification for accounts that already exist (promotion). Link-only:
 * a password is NEVER placed in the email. Brand-new co-admins are invited
 * through CoAdminInviteLinkMail.
 */
class CoAdminInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;

    public string $email;

    public string $schoolName;

    public bool $promoted;

    public string $inviteUrl;

    public ?Carbon $expiresAt;

    public function __construct(
        string $name,
        string $email,
        string $schoolName,
        bool $promoted = false,
        ?string $inviteUrl = null,
        ?Carbon $expiresAt = null,
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->schoolName = $schoolName;
        $this->promoted = $promoted;
        $this->inviteUrl = $inviteUrl ?: route('password.email');
        $this->expiresAt = $expiresAt;
    }

    public function build()
    {
        $mail = $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($this->promoted
                ? "You're now Co-Admin at {$this->schoolName}"
                : "You're invited as Co-Admin at {$this->schoolName}"
            );

        return $mail->markdown($this->promoted ? 'emails.co-admin-promoted' : 'emails.co-admin-invite-link');
    }
}
