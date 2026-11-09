<?php

namespace App\Mail;

use App\Models\User;
use App\Support\MailSender;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $code;

    public function __construct(User $user, int $code)
    {
        $this->user = $user;
        $this->code = $code;
    }

    public function build()
    {
        [$address, $name] = MailSender::resolve();

        return $this->from($address, $name)
            ->subject('Your KlassApp reset code: '.$this->code)
            ->markdown('emails.reset_password_code')
            ->with([
                'name' => $this->user->name,
                'code' => $this->code,
            ]);
    }
}
