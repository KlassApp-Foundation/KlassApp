<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Invite email for new teachers — contains a one-time setup link,
 * NEVER a password.
 */
class TeacherInviteLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;

    public string $schoolName;

    public string $inviteUrl;

    public ?string $className;

    public Carbon $expiresAt;

    public function __construct(
        string $name,
        string $schoolName,
        string $inviteUrl,
        ?string $className = null,
        Carbon $expiresAt = null,
    ) {
        $this->name = $name;
        $this->schoolName = $schoolName;
        $this->inviteUrl = $inviteUrl;
        $this->className = $className !== null && trim($className) !== '' ? trim($className) : null;
        $this->expiresAt = $expiresAt ?? now()->addHours(72);
    }

    public function build()
    {
        $subject = $this->className !== null
            ? "You're invited as class teacher for {$this->className} at {$this->schoolName}"
            : "You've been invited to join {$this->schoolName} on KlassApp";

        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($subject)
            ->markdown('emails.teacher-invite-link');
    }
}
