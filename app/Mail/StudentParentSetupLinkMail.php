<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Setup email for a parent or student who already has an account row.
 * It contains a one-time link and NEVER a password.
 */
class StudentParentSetupLinkMail extends Mailable implements ShouldBeEncrypted
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $schoolName,
        public string $inviteUrl,
        public string $role,
        public Carbon $expiresAt,
    ) {
        $this->role = $this->role === 'parent' ? 'parent' : 'student';
    }

    public function build()
    {
        $roleLabel = $this->role === 'parent' ? 'parent' : 'student';

        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject("Set up your {$roleLabel} account at {$this->schoolName}")
            ->markdown('emails.student-parent-setup-link', [
                'roleLabel' => $roleLabel,
                'hours' => (int) config('invites.expiry_hours', 72),
            ]);
    }
}
