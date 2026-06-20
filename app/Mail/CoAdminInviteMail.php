<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CoAdminInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $password;
    public $schoolName;
    public $promoted;

    public function __construct(string $name, string $email, ?string $password, string $schoolName, bool $promoted = false)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->schoolName = $schoolName;
        $this->promoted = $promoted;
    }

    public function build()
    {
        $mail = $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($this->promoted
                ? "You've been promoted to Co-Admin at {$this->schoolName}"
                : "You're now a Co-Admin at {$this->schoolName}"
            );

        if ($this->promoted) {
            return $mail->markdown('emails.co-admin-promoted');
        }

        return $mail->markdown('emails.co-admin-invite');
    }
}
