<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
        public int $minutes = 15,
    ) {
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Your KlassApp verification code')
            ->view('emails.registration_otp')
            ->with([
                'name' => $this->user->name,
                'otp' => $this->code,
                'minutes' => $this->minutes,
            ]);
    }
}
