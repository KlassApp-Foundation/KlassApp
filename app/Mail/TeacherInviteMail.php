<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeacherInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;

    public string $email;

    public string $password;

    public string $schoolName;

    public ?string $className;

    public string $loginUrl;

    public function __construct(
        string $name,
        string $email,
        string $password,
        string $schoolName,
        ?string $className = null,
        ?string $loginUrl = null,
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->schoolName = $schoolName;
        $this->className = $className !== null && trim($className) !== '' ? trim($className) : null;
        $this->loginUrl = $loginUrl ?: url('/login');
    }

    public function build()
    {
        $subject = $this->className !== null
            ? "You're the class teacher for {$this->className} at {$this->schoolName}"
            : "You've been added as a teacher at {$this->schoolName}";

        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($subject)
            ->markdown('emails.teacher-invite');
    }
}
