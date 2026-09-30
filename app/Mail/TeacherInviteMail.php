<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Legacy teacher notification for accounts that already exist (created by
 * Toshi's addTeacher, or an existing teacher reassigned as class teacher).
 *
 * Link-only: a password is NEVER placed in the email. New accounts get a link
 * to set their own password; existing accounts get a plain "class assigned"
 * notice with a sign-in button.
 */
class TeacherInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;

    public string $email;

    public string $schoolName;

    public ?string $className;

    public string $inviteUrl;

    public ?Carbon $expiresAt;

    /** True when the teacher already has a usable account (reassignment). */
    public bool $existingAccount;

    public function __construct(
        string $name,
        string $email,
        string $schoolName = '',
        ?string $className = null,
        ?string $inviteUrl = null,
        bool $existingAccount = false,
        ?Carbon $expiresAt = null,
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->schoolName = $schoolName;
        $this->className = $className !== null && trim($className) !== '' ? trim($className) : null;
        $this->existingAccount = $existingAccount;
        // New accounts set their password via the reset-code flow; existing ones just sign in.
        $this->inviteUrl = $inviteUrl ?: ($existingAccount ? url('/login') : route('password.email'));
        $this->expiresAt = $expiresAt;
    }

    public function build()
    {
        $subject = $this->className !== null
            ? "You're the class teacher for {$this->className} at {$this->schoolName}"
            : "You've been added as a teacher at {$this->schoolName}";

        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($subject)
            ->markdown($this->existingAccount ? 'emails.teacher-class-assigned' : 'emails.teacher-invite-link');
    }
}
