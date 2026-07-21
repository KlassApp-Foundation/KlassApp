<?php

namespace App\Mail;

use App\Models\User;
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
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Your KlassApp password reset code')
            ->view('emails.reset_password_code')
            ->with([
                'name' => $this->user->name,
                'code' => $this->code,
            ]);
    }
}
