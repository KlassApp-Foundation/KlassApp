<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $otp;

    public function __construct(User $user, int $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Your KlassApp verification code')
            ->view('emails.registration_otp')
            ->with([
                'name' => $this->user->name,
                'otp' => $this->otp,
            ]);
    }
}
