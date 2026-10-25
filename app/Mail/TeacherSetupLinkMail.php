<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Personal class-setup link. Sent only to the address already on the
 * teacher's record. The message holds the link, never a password.
 */
class TeacherSetupLinkMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $schoolName,
        public string $url,
        public Carbon $expiresAt,
    ) {}

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Set up your class')
            ->markdown('emails.teacher-setup-link');
    }
}
